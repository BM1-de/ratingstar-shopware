<?php declare(strict_types=1);

namespace RatingStar\Seal\Service;

use Shopware\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds the markup of one seal.
 *
 * Live mode prints the official embed element and leaves everything else —
 * HTML, CSS, loader token, pixel, plan degrade, transparency notes — to
 * seal.js:
 *
 *   <div class="rs-seal" data-slug="…" data-variant="…" …></div>
 *
 * followed by a tiny loader that adds <script src="<origin>/seal.js" async>
 * to the document unless one is already there. The loader replaces a plain
 * script tag because Shopware renders header and footer as separately cached
 * ESI fragments: a fragment cannot know what the rest of the page contains,
 * so "print the tag once" cannot be decided on the server. Every fragment is
 * self-contained instead, and the browser loads seal.js exactly once per
 * page. The renderer keeps no state between calls.
 *
 * Static mode prints a linked <img> of the server-rendered SVG, without any
 * JavaScript.
 */
class SealRenderer
{
    public function __construct(
        private readonly RatingStarConfig $config,
        private readonly VariantRegistry $variants,
        private readonly TranslatorInterface $translator,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function render(SealOptions $options, ?string $salesChannelId = null): string
    {
        // Tags the HTTP cache entry of this page or fragment, so it is purged
        // together with the cached payloads when a setting changes — also
        // while no profile is configured yet.
        $this->dispatcher->dispatch(new AddCacheTagEvent(RatingStarClient::CACHE_TAG));

        [$isGroup, $handle] = $this->resolveSubject($options, $salesChannelId);

        if ($handle === null) {
            return '<!-- RatingStar: no profile or group configured -->';
        }

        $origin = $this->config->getBaseOrigin($salesChannelId);
        $variant = $this->variants->normalize($options->variant);

        if ($options->mode === SealOptions::MODE_STATIC) {
            $static = $this->renderStatic($options, $origin, $variant, $isGroup, $handle);

            if ($static !== null) {
                return $static;
            }
        }

        $attributes = [
            'class' => 'rs-seal',
            $isGroup ? 'data-group' : 'data-slug' => $handle,
            'data-variant' => $variant,
        ];

        $position = $options->position ?? $options->options['pcPosition'] ?? null;
        $position = $this->variants->normalizePosition($variant, \is_string($position) ? $position : null);

        if ($position !== null) {
            $attributes['data-position'] = $position;
        }

        // Single options first, the free-text attributes on top.
        $attributes = array_replace(
            $attributes,
            $this->variants->optionAttributes($variant, $options->options, $isGroup),
            $this->variants->parseDataAttributes($options->attributes),
        );

        // Contract point 7: with a server-side block, every seal of the page
        // carries the switch — an embed can add it, never take it away.
        if ($options->noRichSnippet === true || $this->printsJsonLd($options, $salesChannelId)) {
            $attributes['data-no-richsnippet'] = '1';
        }

        return '<div' . $this->attributeString($attributes) . '></div>' . "\n" . $this->loader($origin . '/seal.js');
    }

    /**
     * The profile this seal shows: group or branch set on the embed,
     * otherwise the configured one. A group wins over a branch on the same
     * level, so an element never carries both.
     *
     * @return array{0: bool, 1: string|null} [is group, handle]
     */
    private function resolveSubject(SealOptions $options, ?string $salesChannelId): array
    {
        $candidates = [
            [RatingStarConfig::cleanHandle($options->group), RatingStarConfig::cleanHandle($options->slug)],
            [$this->config->getGroup($salesChannelId), $this->config->getSlug($salesChannelId)],
        ];

        foreach ($candidates as [$group, $slug]) {
            if ($group !== null) {
                return [true, $group];
            }

            if ($slug !== null) {
                return [false, $slug];
            }
        }

        return [false, null];
    }

    /**
     * The linked static image, or null when the variant has no static motif
     * (the live widget is rendered instead).
     */
    private function renderStatic(SealOptions $options, string $origin, string $variant, bool $isGroup, string $handle): ?string
    {
        $award = trim((string) $options->award);

        if ($award !== '') {
            // Award badges exist per branch only; the shape is the one the
            // app route accepts (<type>-<level>).
            if ($isGroup || preg_match('/^[a-z_]+-\d+$/', $award) !== 1) {
                return '<!-- RatingStar: award badge not available -->';
            }

            $src = $origin . '/seal/' . rawurlencode($handle) . '/award/' . $award . '.svg';
            $alt = $this->translator->trans('ratingstar.seal.awardAlt');
        } else {
            $svgVariant = $this->variants->staticSvgVariant($variant);

            if ($svgVariant === null) {
                return null;
            }

            $src = $origin . '/seal/' . ($isGroup ? 'g/' : '') . rawurlencode($handle) . '.svg?variant=' . $svgVariant;
            $alt = $this->translator->trans('ratingstar.seal.staticAlt');
        }

        $image = [
            'class' => 'rs-seal-static',
            'src' => $src,
            'alt' => $alt,
            'loading' => 'lazy',
            'decoding' => 'async',
            'style' => 'max-width:100%;height:auto',
        ];

        $size = $options->options['size'] ?? null;

        if (\is_scalar($size) && ctype_digit(trim((string) $size)) && $award === '') {
            $image['width'] = trim((string) $size);
        }

        $link = [
            'class' => 'rs-seal-static-link',
            'href' => $origin . ($isGroup ? '/g/' : '/t/') . rawurlencode($handle),
            'target' => '_blank',
            'rel' => 'noopener',
        ];

        return '<a' . $this->attributeString($link) . '><img' . $this->attributeString($image) . '></a>';
    }

    /**
     * Whether a server-side JSON-LD block is printed on a page that shows
     * this seal: the option is on and a well-formed key exists, in the
     * plugin configuration or next to the seal itself.
     */
    private function printsJsonLd(SealOptions $options, ?string $salesChannelId): bool
    {
        if ($this->config->isServerJsonLdActive($salesChannelId)) {
            return true;
        }

        return $this->config->isServerRichSnippetEnabled($salesChannelId)
            && RatingStarConfig::isValidApiKey(trim((string) $options->apiKey));
    }

    /**
     * Adds <script src="…/seal.js" async> once per document: skipped when a
     * seal.js of the same origin is already there, however it got in.
     */
    private function loader(string $scriptUrl): string
    {
        $url = json_encode($scriptUrl, \JSON_UNESCAPED_SLASHES | \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT);

        return '<script data-rs-seal-loader>(function(d,u){for(var i=0,s=d.scripts,a;i<s.length;i++){'
            . 'a=s[i].src;if(a===u||a.indexOf(u+"?")===0)return;}'
            . 'var e=d.createElement("script");e.src=u;e.async=true;d.head.appendChild(e);'
            . '})(document,' . $url . ');</script>';
    }

    /**
     * @param array<string, string> $attributes
     */
    private function attributeString(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            $html .= ' ' . $name . '="' . htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return $html;
    }
}
