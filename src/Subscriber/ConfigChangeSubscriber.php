<?php declare(strict_types=1);

namespace RatingStar\Seal\Subscriber;

use RatingStar\Seal\Service\RatingStarClient;
use RatingStar\Seal\Service\RatingStarConfig;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Flushes the cached RatingStar payloads whenever a plugin setting changes, so
 * a changed base origin / slug / rotated key takes effect immediately
 * (CONTRACT §2.11).
 */
class ConfigChangeSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly RatingStarClient $client)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigChangedEvent::class => 'onConfigChanged',
        ];
    }

    public function onConfigChanged(SystemConfigChangedEvent $event): void
    {
        if (str_starts_with($event->getKey(), RatingStarConfig::CONFIG_DOMAIN)) {
            $this->client->clearCache();
        }
    }
}
