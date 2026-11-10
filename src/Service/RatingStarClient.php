<?php declare(strict_types=1);

namespace RatingStar\Seal\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Talks to the RatingStar origin server-side. Failures never bubble up: a
 * slow, blocked or broken origin must not break the shop, so every request
 * degrades to "no data". The same goes for a cache that fails.
 *
 * The only payload fetched for the storefront is the finished JSON-LD object
 * of the key-scoped endpoint. Widget data is never fetched here — rendering
 * the seal is the job of seal.js.
 */
class RatingStarClient
{
    /** Tags the cached payloads and every HTTP cache entry that shows a seal. */
    public const CACHE_TAG = 'ratingstar-seal';

    /** JSON-LD is cacheable for 6 hours (endpoint Cache-Control). */
    private const TTL_JSONLD = 21600;

    /** Failures are remembered for 15 minutes so a flaky origin is not hit per page view. */
    private const TTL_FAILURE = 900;

    /** Symfony's "timeout" is the idle timeout only; "max_duration" caps the whole request. */
    private const IDLE_TIMEOUT = 3.0;
    private const MAX_DURATION = 5.0;

    /** No answer of the origin is anywhere near this size. */
    private const MAX_BODY_BYTES = 2_000_000;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly TagAwareAdapterInterface $cache,
        private readonly CacheInvalidator $cacheInvalidator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * The LocalBusiness object of /seal/k/<key>.jsonld, decoded. The app
     * already applies plan and count rules (a bare LocalBusiness below the
     * 4-Star plan or without native reviews). Null on any failure or when
     * the answer is not a LocalBusiness object.
     *
     * The cache can fail (pool folder not writable, Redis not reachable or
     * read-only). Then there is no data and no request: an answer that
     * cannot be stored would be fetched again for every page that is
     * rendered. That is why the entry is saved as a remembered failure
     * before the request — only a cache that takes this very entry gets one.
     *
     * @return array<string, mixed>|null
     */
    public function getJsonLd(string $origin, string $apiKey): ?array
    {
        if (!RatingStarConfig::isValidApiKey($apiKey)) {
            return null;
        }

        $url = $origin . '/seal/k/' . rawurlencode($apiKey) . '.jsonld';

        try {
            $item = $this->cache->getItem('ratingstar_seal_' . sha1($url));

            if ($item->isHit()) {
                $cached = $item->get();

                return \is_array($cached) && $cached !== [] ? $cached : null;
            }

            // An empty array marks a remembered failure.
            if (!$this->store($item, [], self::TTL_FAILURE)) {
                $this->logCacheFailure();

                return null;
            }
        } catch (\Throwable $e) {
            $this->logCacheFailure($e);

            return null;
        }

        $data = $this->requestLocalBusiness($url);

        if ($data === null) {
            // The entry saved above already remembers the failure.
            return null;
        }

        try {
            if (!$this->store($item, $data, self::TTL_JSONLD)) {
                $this->logCacheFailure();
            }
        } catch (\Throwable $e) {
            $this->logCacheFailure($e);
        }

        return $data;
    }

    /**
     * One uncached GET with a hard time and size limit. Status 0 means no
     * usable answer at all (DNS, TLS, timeout, oversized body).
     *
     * @return array{status: int, body: string}
     */
    public function fetch(string $url, string $accept): array
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => self::IDLE_TIMEOUT,
                'max_duration' => self::MAX_DURATION,
                'max_redirects' => 2,
                'headers' => [
                    'Accept' => $accept,
                    'User-Agent' => 'RatingStar-Shopware',
                ],
            ]);

            $status = $response->getStatusCode();
            $body = '';

            foreach ($this->httpClient->stream($response) as $chunk) {
                $body .= $chunk->getContent();

                if (\strlen($body) > self::MAX_BODY_BYTES) {
                    $response->cancel();
                    $this->logger->warning('RatingStar: answer of {url} is too large, ignored.', ['url' => self::maskKey($url)]);

                    return ['status' => 0, 'body' => ''];
                }
            }

            return ['status' => $status, 'body' => $body];
        } catch (HttpExceptionInterface $e) {
            $this->logger->warning('RatingStar: request to {url} failed: {message}', [
                'url' => self::maskKey($url),
                'message' => self::maskKey($e->getMessage()),
            ]);

            return ['status' => 0, 'body' => ''];
        }
    }

    /**
     * Flushes the cached payloads and every HTTP cache entry that shows a
     * seal or the JSON-LD block, so a changed origin, slug or rotated key
     * takes effect at once.
     */
    public function clearCache(): void
    {
        $this->cacheInvalidator->invalidate([self::CACHE_TAG], true);
    }

    /** Hides all but the last four characters of an API key in log output. */
    public static function maskKey(string $text): string
    {
        return (string) preg_replace('/rs_live_[A-Za-z0-9]{36}([A-Za-z0-9]{4})/', 'rs_live_…$1', $text);
    }

    /**
     * @param array<string, mixed> $value
     */
    private function store(CacheItem $item, array $value, int $ttl): bool
    {
        $item->set($value);
        $item->expiresAfter($ttl);
        $item->tag(self::CACHE_TAG);

        return $this->cache->save($item);
    }

    /**
     * Level "error", so the line is also written in the production
     * environment, where warnings only reach the log together with an error.
     */
    private function logCacheFailure(?\Throwable $e = null): void
    {
        $this->logger->error(
            'RatingStar: the cache did not store the JSON-LD entry. Without a working cache there is no JSON-LD block '
            . '(Google stars) and the RatingStar server is not asked. Check that the cache of the shop is writable.',
            $e === null ? [] : ['exception' => $e],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestLocalBusiness(string $url): ?array
    {
        $result = $this->fetch($url, 'application/ld+json, application/json');

        if ($result['status'] === 0) {
            return null;
        }

        if ($result['status'] !== 200) {
            // 403 = plan gate or domain whitelist, 404 = unknown key: expected
            // answers that simply mean "no block".
            $this->logger->info('RatingStar: HTTP {status} for {url}, no JSON-LD.', [
                'status' => $result['status'],
                'url' => self::maskKey($url),
            ]);

            return null;
        }

        $decoded = json_decode($result['body'], true);

        if (!\is_array($decoded) || ($decoded['@type'] ?? null) !== 'LocalBusiness') {
            $this->logger->warning('RatingStar: {url} did not return a LocalBusiness object.', [
                'url' => self::maskKey($url),
            ]);

            return null;
        }

        return $decoded;
    }
}
