<?php declare(strict_types=1);

namespace RatingStar\Seal\Service;

/**
 * Emits the server-side rich snippet: exactly one
 * <script type="application/ld+json"> with the finished LocalBusiness object
 * fetched from the key-scoped endpoint (CONTRACT §2.6–2.8).
 *
 * The object is passed through unchanged — the app already decides bare
 * LocalBusiness vs. aggregateRating by tier/count, so we never invent ratings
 * or emit a null rating. We only refuse to render anything that is not a
 * well-formed LocalBusiness.
 *
 * Request-scoped: $rendered guarantees a single block per page (CONTRACT §2.7).
 */
class JsonLdRenderer
{
    private const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

    private bool $rendered = false;

    public function __construct(
        private readonly RatingStarClient $client,
        private readonly RatingStarConfig $config,
    ) {
    }

    public function render(?string $salesChannelId = null): string
    {
        if ($this->rendered || !$this->config->isServerJsonLdActive($salesChannelId)) {
            return '';
        }

        $data = $this->client->getJsonLdByKey($salesChannelId);

        // Only emit a well-formed LocalBusiness. Anything else (null, 403,
        // unexpected shape) yields nothing rather than broken markup.
        if (!\is_array($data) || ($data['@type'] ?? null) !== 'LocalBusiness') {
            return '';
        }

        $json = json_encode($data, self::JSON_FLAGS);
        if ($json === false) {
            return '';
        }

        $this->rendered = true;

        // JSON_HEX_TAG already neutralises </script>; the data-rs-jsonld marker
        // also makes seal.js skip its own client injection (its H() guard).
        $owner = htmlspecialchars((string) ($this->config->getSlug($salesChannelId) ?? '1'), ENT_QUOTES, 'UTF-8');

        return '<script type="application/ld+json" data-rs-jsonld="' . $owner . '">' . $json . '</script>';
    }
}
