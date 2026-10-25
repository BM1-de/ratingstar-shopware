<?php declare(strict_types=1);

namespace RatingStar\Seal\Service;

/**
 * The canonical seal.js variant set, mirrored verbatim from the app's
 * App\Support\SealConfig::VARIANT_META (CONTRACT §2.5 — the full set must be
 * offered and be identical across all plugins; do not silently drop one).
 *
 * Three things live here so every embed point (Twig function, CMS element)
 * shares one source of truth:
 *   - the full ordered variant list with label + category (static|live)
 *   - alias normalisation (circle → seal-circle, banner → seal-circle-banner)
 *   - which variants take a data-position and the allowed position values
 */
class VariantRegistry
{
    /**
     * @var array<string, array{label: string, category: string}>
     * Order matches the app; labels are the German UI labels from VARIANT_META.
     */
    public const VARIANT_META = [
        'seal-circle'        => ['label' => 'Rundsiegel',       'category' => 'static'],
        'seal-circle-banner' => ['label' => 'Banderole',        'category' => 'static'],
        'profile-card'       => ['label' => 'Profil-Karte',     'category' => 'live'],
        'bar'                => ['label' => 'Bewertungsleiste', 'category' => 'live'],
        'hero'               => ['label' => 'Gesamtnote',       'category' => 'live'],
        'quote'              => ['label' => 'Zitat',            'category' => 'live'],
        'carousel'           => ['label' => 'Karussell',        'category' => 'live'],
        'wall'               => ['label' => 'Bewertungswand',   'category' => 'live'],
        'footer-bar'         => ['label' => 'Fußleiste',        'category' => 'live'],
    ];

    /** seal.js default when data-variant is absent (its `se`/O() fallback). */
    public const DEFAULT_VARIANT = 'seal-circle-banner';

    /**
     * Legacy names accepted by seal.js (SealConfig::VARIANT_ALIAS), mapped to
     * the canonical keys. The former floating badge is the profile card in a
     * screen corner.
     */
    private const ALIASES = [
        'circle'   => 'seal-circle',
        'banner'   => 'seal-circle-banner',
        'card'     => 'profile-card',
        'floating' => 'profile-card',
        'pro'      => 'profile-card',
    ];

    /** Only the profile card carries a data-position (CONTRACT §2.4). */
    public const POSITION_VARIANTS = ['profile-card'];

    /** Allowed data-position values: inline or one of the four corners. */
    public const POSITIONS = ['inline', 'bottom-right', 'bottom-left', 'top-right', 'top-left'];

    /**
     * Per-embed appearance keys seal.js accepts as data attributes, mirrored
     * from EMBED_OVERRIDES in the app's seal-embed.js (CONTRACT §2.17).
     * camelCase key → data-kebab attribute; seal.js validates the values.
     */
    public const OVERRIDE_KEYS = [
        'size',
        'pcPosition', 'pcWidth', 'pcShowCount', 'pcColor', 'pcMobile', 'pcClick',
        'barShowCount', 'barShowVerified',
        'heroShowCount', 'heroShowVerified',
        'quotePick', 'quoteWidth',
        'carCount', 'carRotate', 'carMin', 'carWidth',
        'wallCols', 'wallTotal', 'wallMin', 'wallSort', 'wallWidth',
        'footerBarBg', 'footerBarText', 'footerBarLinkColor', 'footerBarLinkHover', 'footerBarPosition',
        'reviewsOnlyNamed', 'reviewsOnlyWithComment',
        'theme',
    ];

    /** @return list<string> the full canonical variant set, in order. */
    public function all(): array
    {
        return array_keys(self::VARIANT_META);
    }

    /** @return array<string, array{label: string, category: string}> */
    public function meta(): array
    {
        return self::VARIANT_META;
    }

    public function isValid(string $variant): bool
    {
        return isset(self::VARIANT_META[$variant]) || isset(self::ALIASES[$variant]);
    }

    /**
     * Resolve a variant to its canonical key. Aliases map through; unknown
     * values fall back to the default rather than emitting garbage.
     */
    public function normalize(?string $variant): string
    {
        $variant = strtolower(trim((string) $variant));

        if (isset(self::ALIASES[$variant])) {
            return self::ALIASES[$variant];
        }

        return isset(self::VARIANT_META[$variant]) ? $variant : self::DEFAULT_VARIANT;
    }

    public function isLive(string $variant): bool
    {
        $variant = $this->normalize($variant);

        return (self::VARIANT_META[$variant]['category'] ?? null) === 'live';
    }

    public function supportsPosition(string $variant): bool
    {
        return in_array($this->normalize($variant), self::POSITION_VARIANTS, true);
    }

    /**
     * Whitelisted overrides as data attributes (pcColor → data-pc-color).
     * Empty values mean "use the RatingStar settings" and emit nothing, so
     * the app's defaults stay in charge; booleans become '1'/'0'.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, string>
     */
    public function overrideAttributes(array $overrides): array
    {
        $attributes = [];
        foreach (self::OVERRIDE_KEYS as $key) {
            $value = $overrides[$key] ?? null;
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            $attributes['data-' . strtolower((string) preg_replace('/[A-Z]/', '-$0', $key))] = $value;
        }

        return $attributes;
    }

    /** Validate a position value for a variant; null if unsupported/invalid. */
    public function normalizePosition(string $variant, ?string $position): ?string
    {
        if ($position === null || !$this->supportsPosition($variant)) {
            return null;
        }

        $position = strtolower(trim($position));

        return in_array($position, self::POSITIONS, true) ? $position : null;
    }
}
