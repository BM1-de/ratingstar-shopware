<?php declare(strict_types=1);

namespace RatingStar\Seal\Subscriber;

use RatingStar\Seal\Service\RatingStarClient;
use RatingStar\Seal\Service\RatingStarConfig;
use Shopware\Core\System\SystemConfig\Event\SystemConfigMultipleChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Flushes the cached RatingStar payloads and the pages showing a seal as soon
 * as a plugin setting is saved, so a changed origin, slug, group or rotated
 * key takes effect at once.
 */
class ConfigChangeSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly RatingStarClient $client)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigMultipleChangedEvent::class => 'onConfigChanged',
        ];
    }

    public function onConfigChanged(SystemConfigMultipleChangedEvent $event): void
    {
        foreach (array_keys($event->getConfig()) as $key) {
            if (str_starts_with((string) $key, RatingStarConfig::CONFIG_DOMAIN)) {
                $this->client->clearCache();

                return;
            }
        }
    }
}
