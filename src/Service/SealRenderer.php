<?php declare(strict_types=1);

namespace RatingStar\Seal\Service;

/**
 * Builds the official seal embed markup (CONTRACT §2.4):
 *
 *   <div class="rs-seal" data-slug="…" data-variant="…"
 *        [data-position="…"] [data-no-richsnippet="1"]></div>
 *   <script src="<origin>/seal.js" async></script>
 *
 * The <script> is emitted once per render pass even with several widgets —
 * seal.js scans every .rs-seal node itself. We never rebuild the HTML/CSS,
 * loader token, pixel, tier degrade or client JSON-LD; seal.js owns all of
 * that. Every attribute value is HTML-escaped.
 *
 * The service is request-scoped, so $scriptRendered tracks "already emitted on
 * this page" across all embed points (Twig function and, later, CMS element).
 */
class SealRenderer
{
    private bool $scriptRendered = false;

    public function __construct(
        private readonly RatingStarConfig $config,
        private readonly VariantRegistry $variants,
    ) {
    }

    /**
     * @param string|null $variant       canonical key or alias; null → default
     * @param string|null $slug          per-embed slug override; null → config slug
     * @param string|null $position      only applied to floating/footer-bar
     * @param bool|null   $noRichSnippet null → auto: set when this plugin emits
     *                                   server JSON-LD; true/false forces it
     */
    public function render(
        ?string $variant = null,
        ?string $slug = null,
        ?string $position = null,
        ?bool $noRichSnippet = null,
        ?string $salesChannelId = null,
    ): string {
        $slug = $slug !== null && trim($slug) !== '' ? trim($slug) : $this->config->getSlug($salesChannelId);

        // Auto-couple with the server-side JSON-LD: if this plugin emits its own
        // LocalBusiness block, tell seal.js to skip its client injection
        // (CONTRACT §2.7). An explicit bool from the caller wins.
        $noRichSnippet ??= $this->config->isServerJsonLdActive($salesChannelId);

        // Without a slug seal.js has nothing to scan — emit nothing rather than
        // a broken node.
        if ($slug === null) {
            return '';
        }

        $variant = $this->variants->normalize($variant);
        $position = $this->variants->normalizePosition($variant, $position);

        $attrs = sprintf(
            'class="rs-seal" data-slug="%s" data-variant="%s"',
            $this->esc($slug),
            $this->esc($variant),
        );

        if ($position !== null) {
            $attrs .= sprintf(' data-position="%s"', $this->esc($position));
        }

        if ($noRichSnippet) {
            $attrs .= ' data-no-richsnippet="1"';
        }

        $html = sprintf('<div %s></div>', $attrs);

        if (!$this->scriptRendered) {
            $html .= sprintf(
                "\n" . '<script src="%s" async></script>',
                $this->esc($this->config->url('/seal.js', $salesChannelId)),
            );
            $this->scriptRendered = true;
        }

        return $html;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
