<?php declare(strict_types=1);

namespace RatingStar\Seal\Storefront\Twig;

use RatingStar\Seal\Service\JsonLdRenderer;
use RatingStar\Seal\Service\RatingStarConfig;
use RatingStar\Seal\Service\SealOptions;
use RatingStar\Seal\Service\SealRenderer;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Storefront embed points:
 *
 *   {{ rs_seal() }}                         configured profile, default variant
 *   {{ rs_seal(variant='profile-card', position='bottom-left', slug='other-branch') }}
 *   {{ rs_seal(variant='hero', group='my-group', overrides={theme: 'dark'}) }}
 *   {{ rs_seal(variant='carousel', attributes='data-car-count="4"') }}
 *   {{ rs_seal(variant='seal-circle', mode='static') }}
 *
 * rs_seal_element() renders the CMS element "rs-seal" from its slot config
 * and offers the same settings. rs_seal_jsonld() is called once from the
 * head template and prints the server-side rating block.
 */
class SealExtension extends AbstractExtension
{
    public const CMS_ELEMENT_TYPE = 'rs-seal';

    public function __construct(
        private readonly SealRenderer $renderer,
        private readonly JsonLdRenderer $jsonLdRenderer,
    ) {
    }

    public function getFunctions(): array
    {
        $options = ['is_safe' => ['html'], 'needs_context' => true];

        return [
            new TwigFunction('rs_seal', $this->renderSeal(...), $options),
            new TwigFunction('rs_seal_element', $this->renderElement(...), $options),
            new TwigFunction('rs_seal_jsonld', $this->renderJsonLd(...), $options),
        ];
    }

    /**
     * @param array<string, mixed>           $context    the Twig render context
     * @param array<array-key, mixed>|null        $overrides  per-embed options, e.g. {theme: 'dark', carCount: 4}
     * @param string|array<array-key, mixed>|null $attributes extra data attributes, as a string or a map
     */
    public function renderSeal(
        array $context,
        ?string $variant = null,
        ?string $slug = null,
        ?string $position = null,
        ?bool $noRichSnippet = null,
        ?array $overrides = null,
        ?string $group = null,
        string|array|null $attributes = null,
        ?string $mode = null,
        ?string $award = null,
    ): string {
        return $this->renderer->render(
            new SealOptions(
                variant: $variant,
                slug: $slug,
                group: $group,
                position: $position,
                noRichSnippet: $noRichSnippet,
                options: $overrides ?? [],
                attributes: $attributes ?? '',
                mode: $mode === SealOptions::MODE_STATIC ? SealOptions::MODE_STATIC : SealOptions::MODE_LIVE,
                award: $award,
            ),
            $this->resolveSalesChannelId($context),
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderElement(array $context, mixed $element): string
    {
        if (!$element instanceof CmsSlotEntity) {
            return '';
        }

        $values = $this->slotValues($element);
        $string = static fn (string $name): ?string => isset($values[$name]) ? trim((string) $values[$name]) : null;

        return $this->renderer->render(
            new SealOptions(
                variant: $string('variant'),
                slug: $string('slug'),
                group: $string('group'),
                position: $string('position'),
                // The element can only force the attribute on; "automatic" stays null.
                noRichSnippet: $string('noRichSnippet') === '1' ? true : null,
                options: $values,
                attributes: (string) $string('dataAttributes'),
                mode: $string('renderMode') === SealOptions::MODE_STATIC ? SealOptions::MODE_STATIC : SealOptions::MODE_LIVE,
                award: $string('award'),
                apiKey: $string('apiKey'),
            ),
            $this->resolveSalesChannelId($context),
        );
    }

    /**
     * One block per page. The key is, in this order: the argument, the Twig
     * variable "rsSealApiKey" of the page template, the key of a seal element
     * on the page's shopping experience layout, the configured key.
     *
     * @param array<string, mixed> $context
     */
    public function renderJsonLd(array $context, ?string $apiKey = null): string
    {
        $pageKey = $context['rsSealApiKey'] ?? null;

        $apiKey ??= \is_string($pageKey) ? $pageKey : null;
        $apiKey ??= $this->findElementApiKey($context['page'] ?? null);

        return $this->jsonLdRenderer->render($this->resolveSalesChannelId($context), $apiKey);
    }

    /**
     * The first well-formed key set on a seal element of the page layout.
     */
    private function findElementApiKey(mixed $page): ?string
    {
        if (!\is_object($page) || !method_exists($page, 'getCmsPage')) {
            return null;
        }

        $cmsPage = $page->getCmsPage();

        if (!$cmsPage instanceof CmsPageEntity) {
            return null;
        }

        foreach ($cmsPage->getSections() ?? [] as $section) {
            foreach ($section->getBlocks() ?? [] as $block) {
                foreach ($block->getSlots() ?? [] as $slot) {
                    if ($slot->getType() !== self::CMS_ELEMENT_TYPE) {
                        continue;
                    }

                    $key = trim((string) ($this->slotValues($slot)['apiKey'] ?? ''));

                    if (RatingStarConfig::isValidApiKey($key)) {
                        return $key;
                    }
                }
            }
        }

        return null;
    }

    /**
     * The scalar values of a slot config ({field: {source, value}}).
     *
     * @return array<string, bool|float|int|string>
     */
    private function slotValues(CmsSlotEntity $slot): array
    {
        $config = $slot->getTranslation('config');

        if (!\is_array($config)) {
            $config = $slot->getConfig() ?? [];
        }

        $values = [];

        foreach ($config as $name => $field) {
            if (\is_string($name) && \is_array($field) && \is_scalar($field['value'] ?? null)) {
                $values[$name] = $field['value'];
            }
        }

        return $values;
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
