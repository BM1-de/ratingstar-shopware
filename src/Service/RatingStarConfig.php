<?php declare(strict_types=1);

namespace RatingStar\Seal\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Reads the plugin's system configuration and builds every RatingStar URL from
 * the single configurable base origin (CONTRACT §2.2 — no host hardcoded).
 *
 * The two independent handles per sales channel (CONTRACT §2.1):
 *   - slug    → the /t/<slug> handle, drives the slug-based endpoints
 *   - apiKey  → "rs_live_" + 40 alnum, drives the rename-proof key endpoints
 */
class RatingStarConfig
{
    public const CONFIG_DOMAIN = 'RatingStarSeal.config.';
    public const DEFAULT_ORIGIN = 'https://ratingstar.de';

    /** rs_live_ + exactly 40 alphanumeric characters (48 total). */
    private const KEY_PATTERN = '/^rs_live_[A-Za-z0-9]{40}$/';

    public function __construct(private readonly SystemConfigService $systemConfig)
    {
    }

    /**
     * Base origin without trailing slash. Every other URL is derived from this
     * so a staging / self-hosted instance works by changing this one setting.
     */
    public function getBaseOrigin(?string $salesChannelId = null): string
    {
        $value = trim((string) $this->systemConfig->get(self::CONFIG_DOMAIN . 'baseOrigin', $salesChannelId));

        if ($value === '') {
            $value = self::DEFAULT_ORIGIN;
        }

        return rtrim($value, '/');
    }

    public function getSlug(?string $salesChannelId = null): ?string
    {
        $slug = trim((string) $this->systemConfig->get(self::CONFIG_DOMAIN . 'slug', $salesChannelId));

        return $slug !== '' ? $slug : null;
    }

    public function getApiKey(?string $salesChannelId = null): ?string
    {
        $key = trim((string) $this->systemConfig->get(self::CONFIG_DOMAIN . 'apiKey', $salesChannelId));

        return $key !== '' ? $key : null;
    }

    /**
     * True when the configured key matches the rs_live_<40> form. The key
     * endpoints (rename-proof, all tiers open) must only be hit with a
     * well-formed key — never pass free text (CONTRACT §2.1).
     */
    public function hasValidApiKey(?string $salesChannelId = null): bool
    {
        $key = $this->getApiKey($salesChannelId);

        return $key !== null && preg_match(self::KEY_PATTERN, $key) === 1;
    }

    public static function isValidApiKey(string $key): bool
    {
        return preg_match(self::KEY_PATTERN, $key) === 1;
    }

    /** Whether at least one usable handle (slug or valid key) is configured. */
    public function isConfigured(?string $salesChannelId = null): bool
    {
        return $this->getSlug($salesChannelId) !== null || $this->hasValidApiKey($salesChannelId);
    }

    /** Build an absolute RatingStar URL from a path ("/seal.js", "/seal/k/<key>.json", …). */
    public function url(string $path, ?string $salesChannelId = null): string
    {
        return $this->getBaseOrigin($salesChannelId) . '/' . ltrim($path, '/');
    }
}
