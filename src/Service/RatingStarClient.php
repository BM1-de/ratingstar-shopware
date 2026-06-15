<?php declare(strict_types=1);

namespace RatingStar\Seal\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fetches RatingStar JSON payloads server-side and caches them per the TTLs in
 * CONTRACT §1. Failures never bubble up — a broken/blocked RatingStar origin
 * must not break the host shop (CONTRACT §2.9): every fetch degrades to null.
 *
 * All cached items carry the CACHE_TAG so config / key rotation can be flushed
 * in one call (CONTRACT §2.11).
 */
class RatingStarClient
{
    public const CACHE_TAG = 'ratingstar-seal';

    /** TTLs in seconds, mirroring the endpoint table in CONTRACT §1. */
    public const TTL_SLUG_JSON = 60;
    public const TTL_KEY_JSON = 3600;
    public const TTL_JSONLD = 21600;
    public const TTL_REVIEWS = 300;

    private const HTTP_TIMEOUT = 5.0;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly TagAwareAdapterInterface $cache,
        private readonly RatingStarConfig $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Widget/JSON-LD data via the rename-proof, all-tiers-open key endpoint
     * (CONTRACT §1, §2.1 — the preferred path). Null when no valid key.
     */
    public function getSealDataByKey(?string $salesChannelId = null): ?array
    {
        if (!$this->config->hasValidApiKey($salesChannelId)) {
            return null;
        }

        $key = (string) $this->config->getApiKey($salesChannelId);

        return $this->fetchJson($this->config->url('/seal/k/' . rawurlencode($key) . '.json', $salesChannelId), self::TTL_KEY_JSON);
    }

    /**
     * Widget data via the slug endpoint (tier-gated, whitelist-enforced).
     * Fallback when no API key is configured.
     */
    public function getSealDataBySlug(?string $salesChannelId = null): ?array
    {
        $slug = $this->config->getSlug($salesChannelId);
        if ($slug === null) {
            return null;
        }

        return $this->fetchJson($this->config->url('/seal/' . rawurlencode($slug) . '.json', $salesChannelId), self::TTL_SLUG_JSON);
    }

    /** Seal data, preferring the key endpoint and falling back to the slug. */
    public function getSealData(?string $salesChannelId = null): ?array
    {
        return $this->getSealDataByKey($salesChannelId) ?? $this->getSealDataBySlug($salesChannelId);
    }

    /**
     * The finished, server-built JSON-LD object via the key-scoped endpoint
     * (CONTRACT §1 — the recommended path). The app already applies the tier /
     * count logic: a bare LocalBusiness under 4-Star or count 0, otherwise with
     * aggregateRating + reviews. We pass it through unchanged (CONTRACT §2.8).
     * Null when no valid key.
     */
    public function getJsonLdByKey(?string $salesChannelId = null): ?array
    {
        if (!$this->config->hasValidApiKey($salesChannelId)) {
            return null;
        }

        $key = (string) $this->config->getApiKey($salesChannelId);

        return $this->fetchJson($this->config->url('/seal/k/' . rawurlencode($key) . '.jsonld', $salesChannelId), self::TTL_JSONLD);
    }

    /**
     * GET a RatingStar JSON resource, cached + tagged. Returns the decoded
     * array, or null on any transport/HTTP/decoding failure (graceful).
     */
    public function fetchJson(string $url, int $ttl): ?array
    {
        $item = $this->cache->getItem('ratingstar_seal_' . sha1($url));

        if ($item->isHit()) {
            $cached = $item->get();

            return \is_array($cached) ? $cached : null;
        }

        $data = $this->requestJson($url);

        // Cache the result (including a negative null) so a flaky origin is not
        // hammered once per request; null TTL is kept short.
        $item->set($data);
        $item->expiresAfter($data === null ? min($ttl, 60) : $ttl);
        $item->tag(self::CACHE_TAG);
        $this->cache->save($item);

        return $data;
    }

    private function requestJson(string $url): ?array
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => self::HTTP_TIMEOUT,
                'headers' => ['Accept' => 'application/json'],
            ]);

            $status = $response->getStatusCode();

            // 403 = tier gate / domain whitelist / embed not authorized — expected,
            // handled by degrading the widget, not an error (CONTRACT §2.9, §2.10).
            if ($status === 403) {
                $this->logger->info('RatingStar: 403 for {url} (tier/whitelist) — degrading.', ['url' => $url]);

                return null;
            }

            if ($status !== 200) {
                $this->logger->warning('RatingStar: unexpected HTTP {status} for {url}.', ['status' => $status, 'url' => $url]);

                return null;
            }

            $decoded = json_decode($response->getContent(false), true);

            return \is_array($decoded) ? $decoded : null;
        } catch (HttpExceptionInterface $e) {
            $this->logger->warning('RatingStar: request to {url} failed: {msg}', ['url' => $url, 'msg' => $e->getMessage()]);

            return null;
        }
    }

    /** Flush all cached RatingStar payloads (config / key rotation — CONTRACT §2.11). */
    public function clearCache(): void
    {
        $this->cache->invalidateTags([self::CACHE_TAG]);
    }
}
