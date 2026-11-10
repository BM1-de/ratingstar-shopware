<?php declare(strict_types=1);

namespace RatingStar\Seal\Service;

/**
 * Everything one embed point (CMS element or Twig function) can set for a
 * single seal. Null or empty always means "use the plugin configuration" or
 * "default (portal)" — nothing here writes a default into the markup.
 */
final class SealOptions
{
    public const MODE_LIVE = 'live';
    public const MODE_STATIC = 'static';

    /**
     * @param string|null             $variant       canonical key or legacy alias
     * @param string|null             $slug          branch profile of this embed
     * @param string|null             $group         group of this embed; wins over the slug
     * @param string|null             $position      data-position of the profile card
     * @param bool|null               $noRichSnippet true = always set; otherwise automatic (set when the plugin prints its own JSON-LD)
     * @param array<array-key, mixed> $options       per-embed options, camelCase key => value
     * @param string|array<array-key, mixed> $attributes extra data attributes; win over $options
     * @param string                  $mode          live (seal.js) or static (linked image)
     * @param string|null             $award         award badge "<type>-<level>" for the static mode
     * @param string|null             $apiKey        key of a page-level JSON-LD block set next to this seal
     */
    public function __construct(
        public readonly ?string $variant = null,
        public readonly ?string $slug = null,
        public readonly ?string $group = null,
        public readonly ?string $position = null,
        public readonly ?bool $noRichSnippet = null,
        public readonly array $options = [],
        public readonly string|array $attributes = '',
        public readonly string $mode = self::MODE_LIVE,
        public readonly ?string $award = null,
        public readonly ?string $apiKey = null,
    ) {
    }
}
