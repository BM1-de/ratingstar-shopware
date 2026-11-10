<?php declare(strict_types=1);

namespace RatingStar\Seal\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Reads the plugin's system configuration (per sales channel, inheriting the
 * global value) and builds every RatingStar URL from the one configurable
 * base origin — no host is hardcoded anywhere else.
 *
 * Handles per sales channel:
 *   - slug    the /t/<slug> handle of a branch profile
 *   - group   the /g/<group> handle of a group; wins over the slug
 *   - apiKey  "rs_live_" + 40 alphanumeric characters, of the branch or the
 *             group; drives the key-scoped JSON-LD endpoint
 */
class RatingStarConfig
{
    public const CONFIG_DOMAIN = 'RatingStarSeal.config.';
    public const DEFAULT_ORIGIN = 'https://ratingstar.de';

    /** rs_live_ + exactly 40 alphanumeric characters (48 in total). */
    private const KEY_PATTERN = '/^rs_live_[A-Za-z0-9]{40}$/';

    /** Profile and group slugs: URL-safe characters only. */
    private const HANDLE_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]*$/';

    public function __construct(private readonly SystemConfigService $systemConfig)
    {
    }

    /**
     * Base origin (scheme://host[:port]) without a trailing slash. Anything
     * that is not an http(s) URL falls back to the default origin.
     */
    public function getBaseOrigin(?string $salesChannelId = null): string
    {
        return self::normalizeOrigin($this->getString('baseOrigin', $salesChannelId)) ?? self::DEFAULT_ORIGIN;
    }

    public function getSlug(?string $salesChannelId = null): ?string
    {
        return self::cleanHandle($this->getString('slug', $salesChannelId));
    }

    public function getGroup(?string $salesChannelId = null): ?string
    {
        return self::cleanHandle($this->getString('group', $salesChannelId));
    }

    /**
     * The configured API key, or null when it is empty or does not have the
     * rs_live_<40> form. The key endpoints are never called with free text.
     */
    public function getApiKey(?string $salesChannelId = null): ?string
    {
        $key = $this->getString('apiKey', $salesChannelId);

        return self::isValidApiKey($key) ? $key : null;
    }

    /** Whether the server-side rich snippet is switched on (default: on). */
    public function isServerRichSnippetEnabled(?string $salesChannelId = null): bool
    {
        $value = $this->systemConfig->get(self::CONFIG_DOMAIN . 'serverRichSnippet', $salesChannelId);

        // Shopware returns null for a bool that was never saved.
        return $value === null ? true : (bool) $value;
    }

    /**
     * True when the plugin prints its own JSON-LD block for this sales
     * channel: the option is on and a well-formed key is configured. Seals
     * then carry data-no-richsnippet="1" so seal.js adds no second block.
     */
    public function isServerJsonLdActive(?string $salesChannelId = null): bool
    {
        return $this->isServerRichSnippetEnabled($salesChannelId) && $this->getApiKey($salesChannelId) !== null;
    }

    /** Absolute RatingStar URL for a path such as "/seal.js". */
    public function url(string $path, ?string $salesChannelId = null): string
    {
        return $this->getBaseOrigin($salesChannelId) . '/' . ltrim($path, '/');
    }

    public static function isValidApiKey(string $key): bool
    {
        return preg_match(self::KEY_PATTERN, $key) === 1;
    }

    /** The trimmed slug if it only holds URL-safe characters, otherwise null. */
    public static function cleanHandle(?string $handle): ?string
    {
        $handle = trim((string) $handle);

        return preg_match(self::HANDLE_PATTERN, $handle) === 1 ? $handle : null;
    }

    /**
     * Reduces a configured URL to its origin. Returns null for anything that
     * is not an http(s) URL with a host, so callers can fall back.
     */
    public static function normalizeOrigin(?string $origin): ?string
    {
        $parts = parse_url(trim((string) $origin));

        if (!\is_array($parts) || !isset($parts['host']) || $parts['host'] === '') {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if ($scheme !== 'http' && $scheme !== 'https') {
            return null;
        }

        // Only host characters may reach the markup built from this value.
        if (preg_match('/^[A-Za-z0-9.\-\[\]:]+$/', $parts['host']) !== 1) {
            return null;
        }

        return $scheme . '://' . strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    private function getString(string $key, ?string $salesChannelId): string
    {
        $value = $this->systemConfig->get(self::CONFIG_DOMAIN . $key, $salesChannelId);

        return \is_scalar($value) ? trim((string) $value) : '';
    }
}
