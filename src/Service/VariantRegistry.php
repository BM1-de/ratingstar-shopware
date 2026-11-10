<?php declare(strict_types=1);

namespace RatingStar\Seal\Service;

/**
 * What seal.js accepts on a .rs-seal element, mirrored from the RatingStar
 * app: the nine variants (SealConfig::VARIANT_META), their legacy aliases,
 * the positions of the profile card and the per-embed options
 * (EMBED_OVERRIDES in seal-embed.js, grouped per variant like the code
 * generator of the portal).
 *
 * The administration keeps the same lists for its form in
 * Resources/app/administration/src/constant/rs-seal.constant.js — change
 * both together.
 */
class VariantRegistry
{
    /** Canonical data-variant values, in the order of the app. */
    public const VARIANTS = [
        // static seals (every plan)
        'seal-circle',
        'seal-circle-banner',
        // live widgets (4-Star plan and up; seal.js degrades below)
        'profile-card',
        'bar',
        'hero',
        'quote',
        'carousel',
        'wall',
        'footer-bar',
    ];

    /** What seal.js renders for an unknown data-variant. */
    public const DEFAULT_VARIANT = 'seal-circle-banner';

    /** Legacy names: still accepted, never offered. */
    private const ALIASES = [
        'circle' => 'seal-circle',
        'banner' => 'seal-circle-banner',
        'card' => 'profile-card',
        'floating' => 'profile-card',
        'pro' => 'profile-card',
    ];

    /** data-position values; only the profile card has a position. */
    public const POSITIONS = ['inline', 'bottom-right', 'bottom-left', 'top-right', 'top-left'];

    /**
     * Variants that exist as a static SVG, mapped to the ?variant= value of
     * the SVG endpoint. The static image mode is limited to these, so a live
     * widget is never silently swapped for a different motif.
     */
    public const STATIC_SVG_MAP = [
        'seal-circle' => 'circle',
        'seal-circle-banner' => 'banner',
        'profile-card' => 'card',
    ];

    /**
     * Per-embed options per variant; each becomes a data-<kebab-case>
     * attribute. The position of the profile card (pcPosition) is carried by
     * data-position and therefore not listed here.
     */
    public const VARIANT_OPTIONS = [
        'seal-circle' => ['size'],
        'seal-circle-banner' => ['size'],
        'profile-card' => ['pcWidth', 'pcShowCount', 'pcColor', 'pcMobile', 'pcClick'],
        'bar' => ['barShowCount', 'barShowVerified', 'theme'],
        'hero' => ['heroShowCount', 'heroShowVerified', 'heroShowBranches', 'theme'],
        'quote' => ['quotePick', 'quoteWidth', 'reviewsOnlyNamed', 'reviewsOnlyWithComment', 'theme'],
        'carousel' => ['carCount', 'carRotate', 'carMin', 'carWidth', 'reviewsOnlyNamed', 'reviewsOnlyWithComment', 'theme'],
        'wall' => ['wallCols', 'wallTotal', 'wallMin', 'wallSort', 'wallWidth', 'reviewsOnlyNamed', 'reviewsOnlyWithComment', 'theme'],
        'footer-bar' => ['footerBarPosition', 'footerBarBg', 'footerBarText', 'footerBarLinkColor', 'footerBarLinkHover'],
    ];

    /**
     * Value shape per option. Ranges and enum values are enforced by seal.js,
     * which falls back to the portal setting for anything invalid.
     */
    private const OPTION_TYPES = [
        'size' => 'int',
        'pcWidth' => 'int',
        'pcShowCount' => 'bool',
        'pcColor' => 'enum',
        'pcMobile' => 'enum',
        'pcClick' => 'enum',
        'barShowCount' => 'bool',
        'barShowVerified' => 'bool',
        'heroShowCount' => 'bool',
        'heroShowVerified' => 'bool',
        'heroShowBranches' => 'bool',
        'quotePick' => 'enum',
        'quoteWidth' => 'enum',
        'carCount' => 'int',
        'carRotate' => 'int',
        'carMin' => 'int',
        'carWidth' => 'enum',
        'wallCols' => 'int',
        'wallTotal' => 'int',
        'wallMin' => 'int',
        'wallSort' => 'enum',
        'wallWidth' => 'enum',
        'footerBarPosition' => 'enum',
        'footerBarBg' => 'color',
        'footerBarText' => 'color',
        'footerBarLinkColor' => 'color',
        'footerBarLinkHover' => 'color',
        'reviewsOnlyNamed' => 'bool',
        'reviewsOnlyWithComment' => 'bool',
        'theme' => 'enum',
    ];

    /**
     * Attributes the free-text field must not set: identity, variant,
     * position and the rich-snippet switch have their own settings, the
     * preview attributes belong to the RatingStar portal alone.
     */
    public const RESERVED_ATTRIBUTES = [
        'data-slug',
        'data-group',
        'data-variant',
        'data-position',
        'data-no-richsnippet',
        'data-preview-config',
        'data-preview-data',
        'data-preview-svg',
    ];

    /** Canonical key for a variant; aliases map through, unknown values fall back. */
    public function normalize(?string $variant): string
    {
        $variant = strtolower(trim((string) $variant));
        $variant = self::ALIASES[$variant] ?? $variant;

        return \in_array($variant, self::VARIANTS, true) ? $variant : self::DEFAULT_VARIANT;
    }

    /** A valid data-position for the variant, or null (no attribute). */
    public function normalizePosition(string $variant, ?string $position): ?string
    {
        if ($variant !== 'profile-card') {
            return null;
        }

        $position = strtolower(trim((string) $position));

        return \in_array($position, self::POSITIONS, true) ? $position : null;
    }

    /** The ?variant= value of the static SVG, or null if none exists. */
    public function staticSvgVariant(string $variant): ?string
    {
        return self::STATIC_SVG_MAP[$variant] ?? null;
    }

    /**
     * The options of one variant as data attributes (pcColor →
     * data-pc-color). An empty value means "default (portal)" and yields no
     * attribute, so the settings made in RatingStar are never overruled by a
     * form default. Options of other variants are dropped.
     *
     * @param array<array-key, mixed> $options camelCase option key => value
     *
     * @return array<string, string>
     */
    public function optionAttributes(string $variant, array $options, bool $isGroup): array
    {
        $attributes = [];

        foreach (self::VARIANT_OPTIONS[$variant] ?? [] as $key) {
            // The rating per branch only exists on group seals.
            if ($key === 'heroShowBranches' && !$isGroup) {
                continue;
            }

            $value = self::checkedValue(self::OPTION_TYPES[$key], $options[$key] ?? null);

            if ($value !== null) {
                $attributes[self::attributeName($key)] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Splits raw attributes (data-size="240" data-pc-color='weiss'
     * data-car-count=4, or a whole pasted embed code) into data attributes.
     * Only data-* names survive, reserved ones are dropped; the values are
     * escaped on output and validated by seal.js.
     *
     * @param string|array<array-key, mixed> $raw
     *
     * @return array<string, string>
     */
    public function parseDataAttributes(string|array $raw): array
    {
        $attributes = [];

        if (\is_array($raw)) {
            foreach ($raw as $name => $value) {
                $name = strtolower(trim((string) $name));

                if (preg_match('/^data-[a-z][a-z0-9-]*$/', $name) === 1 && \is_scalar($value)) {
                    $attributes[$name] = \is_bool($value) ? ($value ? '1' : '0') : (string) $value;
                }
            }
        } else {
            preg_match_all(
                '/(?<![\w-])(data-[a-z][a-z0-9-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/i',
                $raw,
                $matches,
                \PREG_SET_ORDER | \PREG_UNMATCHED_AS_NULL
            );

            foreach ($matches as $match) {
                $value = $match[2] ?? $match[3] ?? $match[4] ?? '';
                $attributes[strtolower($match[1])] = html_entity_decode($value, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
            }
        }

        return array_diff_key($attributes, array_flip(self::RESERVED_ATTRIBUTES));
    }

    private static function attributeName(string $key): string
    {
        return 'data-' . strtolower((string) preg_replace('/[A-Z]/', '-$0', $key));
    }

    /** The value as an attribute string if it has the expected shape, else null. */
    private static function checkedValue(string $type, mixed $value): ?string
    {
        if (\is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        // Arrays and objects (e.g. passed from Twig by mistake) are ignored.
        if (!\is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        switch ($type) {
            case 'bool':
                return match ($value) {
                    '1', 'true' => '1',
                    '0', 'false' => '0',
                    default => null,
                };

            case 'int':
                return ctype_digit($value) ? $value : null;

            case 'color':
                // seal.js takes six-digit hex colours only.
                if (preg_match('/^#([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])$/', $value, $m) === 1) {
                    $value = '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
                }

                return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : null;

            default:
                return preg_match('/^[a-z0-9-]+$/', $value) === 1 ? $value : null;
        }
    }
}
