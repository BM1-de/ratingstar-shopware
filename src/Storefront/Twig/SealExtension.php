<?php declare(strict_types=1);

namespace RatingStar\Seal\Storefront\Twig;

use RatingStar\Seal\Service\JsonLdRenderer;
use RatingStar\Seal\Service\SealRenderer;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Storefront embed points (CONTRACT §2.13):
 *
 *   {{ rs_seal() }}                                {# config slug, default variant #}
 *   {{ rs_seal(variant='profile-card', position='bottom-left', slug='other-branch') }}
 *   {{ rs_seal(variant='carousel', overrides={theme: 'dark', carCount: 4}) }}
 *   {{ rs_seal_jsonld() }}                         {# server LocalBusiness in <head> #}
 *
 * rs_seal takes per-call slug + variant (+ position) overrides so this point
 * never silently falls back to global-only, plus the per-embed appearance
 * keys of seal.js (CONTRACT §2.17) as `overrides`. rs_seal_jsonld is wired into the
 * head template and emits the server-side rich snippet once.
 */
class SealExtension extends AbstractExtension
{
    public function __construct(
        private readonly SealRenderer $renderer,
        private readonly JsonLdRenderer $jsonLdRenderer,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'rs_seal',
                $this->renderSeal(...),
                ['is_safe' => ['html'], 'needs_context' => true],
            ),
            new TwigFunction(
                'rs_seal_jsonld',
                $this->renderJsonLd(...),
                ['is_safe' => ['html'], 'needs_context' => true],
            ),
        ];
    }

    /**
     * @param array<string, mixed> $context the Twig render context
     */
    public function renderSeal(
        array $context,
        ?string $variant = null,
        ?string $slug = null,
        ?string $position = null,
        ?bool $noRichSnippet = null,
        array $overrides = [],
    ): string {
        return $this->renderer->render(
            $variant,
            $slug,
            $position,
            $noRichSnippet,
            $this->resolveSalesChannelId($context),
            $overrides,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderJsonLd(array $context): string
    {
        return $this->jsonLdRenderer->render($this->resolveSalesChannelId($context));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveSalesChannelId(array $context): ?string
    {
        $salesChannelContext = $context['context'] ?? null;

        return $salesChannelContext instanceof SalesChannelContext
            ? $salesChannelContext->getSalesChannelId()
            : null;
    }
}
