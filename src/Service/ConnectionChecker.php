<?php declare(strict_types=1);

namespace RatingStar\Seal\Service;

/**
 * Checks profile slug, group slug and API key against the RatingStar origin
 * for the "check connection" button of the plugin configuration. The check
 * only informs: nothing is stored or blocked here, so a value can always be
 * saved and corrected later.
 *
 * Each check answers with a status and a code the administration translates:
 *
 *   ok       found          the profile exists; "name" carries its name
 *   warning  not_found      the origin knows no such profile or key (404)
 *   warning  invalid        the value does not have the expected form
 *   warning  http_error     unexpected answer; "httpStatus" carries the code
 *   warning  unreachable    no answer at all (DNS, TLS, timeout)
 *   skipped  empty          nothing entered
 */
class ConnectionChecker
{
    public function __construct(private readonly RatingStarClient $client)
    {
    }

    /**
     * @return array{origin: string|null, checks: list<array<string, int|string|null>>}
     */
    public function check(string $baseOrigin, string $slug, string $group, string $apiKey): array
    {
        $origin = trim($baseOrigin) === ''
            ? RatingStarConfig::DEFAULT_ORIGIN
            : RatingStarConfig::normalizeOrigin($baseOrigin);

        if ($origin === null) {
            return [
                'origin' => null,
                'checks' => [self::result('origin', 'warning', 'invalid')],
            ];
        }

        return [
            'origin' => $origin,
            'checks' => [
                $this->checkProfile('slug', $origin . '/t/', $slug),
                $this->checkProfile('group', $origin . '/g/', $group),
                $this->checkApiKey($origin, $apiKey),
            ],
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function checkProfile(string $subject, string $baseUrl, string $handle): array
    {
        $handle = trim($handle);

        if ($handle === '') {
            return self::result($subject, 'skipped', 'empty');
        }

        if (RatingStarConfig::cleanHandle($handle) === null) {
            return self::result($subject, 'warning', 'invalid');
        }

        $response = $this->client->fetch($baseUrl . rawurlencode($handle), 'text/html');

        if ($response['status'] !== 200) {
            return self::failure($subject, $response['status']);
        }

        return self::result($subject, 'ok', 'found', self::profileName($response['body']) ?? $handle);
    }

    /**
     * @return array<string, int|string|null>
     */
    private function checkApiKey(string $origin, string $apiKey): array
    {
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            return self::result('apiKey', 'skipped', 'empty');
        }

        if (!RatingStarConfig::isValidApiKey($apiKey)) {
            return self::result('apiKey', 'warning', 'invalid');
        }

        $response = $this->client->fetch(
            $origin . '/seal/k/' . rawurlencode($apiKey) . '.jsonld',
            'application/ld+json, application/json'
        );

        if ($response['status'] !== 200) {
            return self::failure('apiKey', $response['status']);
        }

        $data = json_decode($response['body'], true);

        if (!\is_array($data) || ($data['@type'] ?? null) !== 'LocalBusiness') {
            return self::result('apiKey', 'warning', 'http_error', null, 200);
        }

        $name = $data['name'] ?? null;

        return self::result('apiKey', 'ok', 'found', \is_string($name) ? $name : null);
    }

    /**
     * The profile name from the page title ("Name · Bewertungen · RatingStar").
     */
    private static function profileName(string $html): ?string
    {
        // The title sits at the top of the document.
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', substr($html, 0, 65536), $match) !== 1) {
            return null;
        }

        $title = strip_tags($match[1]);

        // Decode twice: the title may arrive double-encoded.
        for ($i = 0; $i < 2; ++$i) {
            $title = html_entity_decode($title, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        }

        $title = (string) preg_replace('/\s*[·|–-]\s*RatingStar\s*$/u', '', $title);
        $title = (string) preg_replace('/\s*[·|–-]\s*(Gruppen-Bewertungen|Bewertungen|Group reviews|Reviews)\s*$/ui', '', $title);
        $title = trim((string) preg_replace('/\s+/u', ' ', $title));

        return $title !== '' ? mb_substr($title, 0, 200) : null;
    }

    /**
     * @return array<string, int|string|null>
     */
    private static function failure(string $subject, int $status): array
    {
        return match ($status) {
            0 => self::result($subject, 'warning', 'unreachable'),
            404 => self::result($subject, 'warning', 'not_found', null, 404),
            default => self::result($subject, 'warning', 'http_error', null, $status),
        };
    }

    /**
     * @return array<string, int|string|null>
     */
    private static function result(string $subject, string $status, string $code, ?string $name = null, ?int $httpStatus = null): array
    {
        return [
            'subject' => $subject,
            'status' => $status,
            'code' => $code,
            'name' => $name,
            'httpStatus' => $httpStatus,
        ];
    }
}
