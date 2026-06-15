<?php declare(strict_types=1);

namespace RatingStar\Seal\Storefront\Twig;

use RatingStar\Seal\Service\SealRenderer;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Storefront embed point for theme developers (CONTRACT §2.13): a single Twig
 * function that takes per-call slug + variant (+ position) overrides, so this
 * point never silently falls back to global-only.
 *
 *   {{ rs_seal() }}                               {# config slug, default variant #}
 *   {{ rs_seal(variant='bar') }}
 *   {{ rs_seal(variant='floating', position='bottom-left', slug='other-branch') }}
 */
class SealExtension extends AbstractExtension
{
    public function __construct(private readonly SealRenderer $renderer)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'rs_seal',
                $this->renderSeal(...),
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
        bool $noRichSnippet = false,
    ): string {
        return $this->renderer->render(
            $variant,
            $slug,
            $position,
            $noRichSnippet,
            $this->resolveSalesChannelId($context),
        );
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
