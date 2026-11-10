/**
 * What seal.js accepts on a .rs-seal element, mirrored from the RatingStar
 * app (SealConfig::VARIANT_META, EMBED_OVERRIDES in seal-embed.js, grouped
 * per variant like the code generator of the portal).
 *
 * The storefront keeps the same lists in src/Service/VariantRegistry.php —
 * change both together.
 */

/** Type of the shopping experience element and of its block. */
export const ELEMENT_NAME = 'rs-seal';

/** Canonical data-variant values, in the order of the app. */
export const VARIANTS = [
    'seal-circle',
    'seal-circle-banner',
    'profile-card',
    'bar',
    'hero',
    'quote',
    'carousel',
    'wall',
    'footer-bar',
];

export const DEFAULT_VARIANT = 'seal-circle-banner';

/** Variants that also exist as a static image (no JavaScript). */
export const STATIC_VARIANTS = ['seal-circle', 'seal-circle-banner', 'profile-card'];

/** data-position values of the profile card. */
export const POSITIONS = ['inline', 'bottom-right', 'bottom-left', 'top-right', 'top-left'];

/** Per-embed options per variant. */
export const VARIANT_OPTIONS = {
    'seal-circle': ['size'],
    'seal-circle-banner': ['size'],
    'profile-card': ['pcWidth', 'pcShowCount', 'pcColor', 'pcMobile', 'pcClick'],
    bar: ['barShowCount', 'barShowVerified', 'theme'],
    hero: ['heroShowCount', 'heroShowVerified', 'heroShowBranches', 'theme'],
    quote: ['quotePick', 'quoteWidth', 'reviewsOnlyNamed', 'reviewsOnlyWithComment', 'theme'],
    carousel: ['carCount', 'carRotate', 'carMin', 'carWidth', 'reviewsOnlyNamed', 'reviewsOnlyWithComment', 'theme'],
    wall: ['wallCols', 'wallTotal', 'wallMin', 'wallSort', 'wallWidth', 'reviewsOnlyNamed', 'reviewsOnlyWithComment', 'theme'],
    'footer-bar': ['footerBarPosition', 'footerBarBg', 'footerBarText', 'footerBarLinkColor', 'footerBarLinkHover'],
};

/**
 * Type, range and values per option. Ranges only feed the labels; seal.js
 * clamps numbers and ignores unknown values itself.
 */
export const OPTIONS = {
    size: { type: 'int', min: 120, max: 320 },
    pcWidth: { type: 'int', min: 100, max: 215 },
    pcShowCount: { type: 'bool' },
    pcColor: { type: 'enum', values: ['gold', 'silber', 'weiss'] },
    pcMobile: { type: 'enum', values: ['compact', 'hide', 'full'] },
    pcClick: { type: 'enum', values: ['overlay', 'profile'] },
    barShowCount: { type: 'bool' },
    barShowVerified: { type: 'bool' },
    heroShowCount: { type: 'bool' },
    heroShowVerified: { type: 'bool' },
    heroShowBranches: { type: 'bool' },
    quotePick: { type: 'enum', values: ['best', 'newest', 'rotate', 'manual'] },
    quoteWidth: { type: 'enum', values: ['s', 'm', 'l', 'full'] },
    carCount: { type: 'int', min: 3, max: 20 },
    carRotate: { type: 'int', min: 0, max: 30 },
    carMin: { type: 'int', min: 1, max: 5 },
    carWidth: { type: 'enum', values: ['s', 'm', 'l', 'full'] },
    wallCols: { type: 'int', min: 2, max: 4 },
    wallTotal: { type: 'int', min: 2, max: 50 },
    wallMin: { type: 'int', min: 1, max: 5 },
    wallSort: { type: 'enum', values: ['newest', 'best', 'mix'] },
    wallWidth: { type: 'enum', values: ['m', 'l', 'xl', 'full'] },
    footerBarPosition: { type: 'enum', values: ['fixed', 'inline'] },
    footerBarBg: { type: 'color' },
    footerBarText: { type: 'color' },
    footerBarLinkColor: { type: 'color' },
    footerBarLinkHover: { type: 'color' },
    reviewsOnlyNamed: { type: 'bool' },
    reviewsOnlyWithComment: { type: 'bool' },
    theme: { type: 'enum', values: ['light', 'dark'] },
};

/**
 * Slot config of a new element. Every option starts empty, which means
 * "default (portal)": no attribute is printed and the settings made in
 * RatingStar stay in charge.
 */
export function defaultElementConfig() {
    const config = {
        variant: { source: 'static', value: DEFAULT_VARIANT },
        renderMode: { source: 'static', value: 'live' },
        position: { source: 'static', value: '' },
        slug: { source: 'static', value: '' },
        group: { source: 'static', value: '' },
        award: { source: 'static', value: '' },
        dataAttributes: { source: 'static', value: '' },
        apiKey: { source: 'static', value: '' },
        noRichSnippet: { source: 'static', value: '' },
    };

    Object.keys(OPTIONS).forEach((key) => {
        config[key] = { source: 'static', value: '' };
    });

    return config;
}
