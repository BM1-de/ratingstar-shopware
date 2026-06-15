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
        'seal-circle'        => ['label' => 'Rundsiegel',     'category' => 'static'],
        'seal-circle-banner' => ['label' => 'Banderole',      'category' => 'static'],
        'profile-card'       => ['label' => 'Profil-Karte',   'category' => 'live'],
        'bar'                => ['label' => 'Trust-Bar',      'category' => 'live'],
        'floating'           => ['label' => 'Floating Badge', 'category' => 'live'],
        'hero'               => ['label' => 'Hero-Snippet',   'category' => 'live'],
        'quote'              => ['label' => 'Featured Quote', 'category' => 'live'],
        'carousel'           => ['label' => 'Carousel',       'category' => 'live'],
        'wall'               => ['label' => 'Wall of Love',   'category' => 'live'],
        'footer-bar'         => ['label' => 'Footer-Bar',     'category' => 'live'],
    ];

    /** seal.js default when data-variant is absent (its `se`/O() fallback). */
    public const DEFAULT_VARIANT = 'seal-circle-banner';

    /** Short aliases accepted by seal.js, mapped to the canonical keys. */
    private const ALIASES = [
        'circle' => 'seal-circle',
        'banner' => 'seal-circle-banner',
    ];

    /** Variants that carry a data-position (CONTRACT §2.4). */
    public const POSITION_VARIANTS = ['floating', 'footer-bar'];

    /** Allowed data-position values (seal.js O() for the floating/pro variant). */
    public const POSITIONS = ['bottom-right', 'bottom-left', 'top-right', 'top-left', 'inline'];

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
