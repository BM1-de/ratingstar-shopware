<?php declare(strict_types=1);

namespace RatingStar\Seal\Service;

use Shopware\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Prints the server-side rich snippet: one
 * <script type="application/ld+json"> with the rating object of the
 * key-scoped endpoint (LocalBusiness or Product, as set in RatingStar). It needs no connection from the visitor's browser to
 * RatingStar.
 *
 * The remote answer never reaches the page as raw text: it is decoded,
 * checked for an accepted "@type" and encoded again with the JSON_HEX_*
 * flags, so a company name or review text containing "</script>" cannot end
 * the block. The content itself is passed through unchanged — plan and count
 * rules are applied by the app.
 *
 * "One block per page" follows from the single call in the head template;
 * the renderer keeps no state between calls.
 */
class JsonLdRenderer
{
    private const JSON_FLAGS = \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE
        | \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT;

    public function __construct(
        private readonly RatingStarClient $client,
        private readonly RatingStarConfig $config,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @param string|null $apiKey key of the branch or group this page stands
     *                            for; null or malformed = configured key
     */
    public function render(?string $salesChannelId = null, ?string $apiKey = null): string
    {
        // Tags the HTTP cache entry of this page, so it is purged together
        // with the cached payload when a setting changes — also while the
        // block is switched off or no key is configured yet.
        $this->dispatcher->dispatch(new AddCacheTagEvent(RatingStarClient::CACHE_TAG));

        if (!$this->config->isServerRichSnippetEnabled($salesChannelId)) {
            return '';
        }

        $apiKey = trim((string) $apiKey);
        $key = RatingStarConfig::isValidApiKey($apiKey) ? $apiKey : $this->config->getApiKey($salesChannelId);

        if ($key === null) {
            return '';
        }

        $data = $this->client->getJsonLd($this->config->getBaseOrigin($salesChannelId), $key);

        if ($data === null || !RatingStarClient::isRatingObject($data)) {
            return '';
        }

        $json = json_encode($data, self::JSON_FLAGS);

        if ($json === false) {
            return '';
        }

        // seal.js skips its own client-side block when it finds data-rs-jsonld.
        return '<script type="application/ld+json" data-rs-jsonld="1">' . $json . '</script>';
    }
}
