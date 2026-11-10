<?php declare(strict_types=1);

use RatingStar\Seal\Command\ClearCacheCommand;
use RatingStar\Seal\Controller\ConnectionCheckController;
use RatingStar\Seal\Service\ConnectionChecker;
use RatingStar\Seal\Service\JsonLdRenderer;
use RatingStar\Seal\Service\RatingStarClient;
use RatingStar\Seal\Service\RatingStarConfig;
use RatingStar\Seal\Service\SealRenderer;
use RatingStar\Seal\Service\VariantRegistry;
use RatingStar\Seal\Storefront\Twig\SealExtension;
use RatingStar\Seal\Subscriber\ConfigChangeSubscriber;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(RatingStarConfig::class)
        ->args([service(SystemConfigService::class)]);

    // A client of its own instead of the shared "http_client": that one logs
    // every request URL on the http_client channel, and the URL of the key
    // endpoint contains the API key.
    $services->set('ratingstar_seal.http_client', HttpClientInterface::class)
        ->factory([HttpClient::class, 'create']);

    $services->set(RatingStarClient::class)
        ->args([
            service('ratingstar_seal.http_client'),
            service('cache.object'),
            service(CacheInvalidator::class),
            service('logger'),
        ]);

    $services->set(VariantRegistry::class);

    $services->set(SealRenderer::class)
        ->args([
            service(RatingStarConfig::class),
            service(VariantRegistry::class),
            service('translator'),
            service('event_dispatcher'),
        ]);

    $services->set(JsonLdRenderer::class)
        ->args([
            service(RatingStarClient::class),
            service(RatingStarConfig::class),
            service('event_dispatcher'),
        ]);

    $services->set(ConnectionChecker::class)
        ->args([service(RatingStarClient::class)]);

    $services->set(SealExtension::class)
        ->args([
            service(SealRenderer::class),
            service(JsonLdRenderer::class),
        ])
        ->tag('twig.extension');

    $services->set(ConfigChangeSubscriber::class)
        ->args([service(RatingStarClient::class)])
        ->tag('kernel.event_subscriber');

    $services->set(ClearCacheCommand::class)
        ->args([service(RatingStarClient::class)])
        ->tag('console.command');

    $services->set(ConnectionCheckController::class)
        ->public()
        ->args([service(ConnectionChecker::class)])
        ->tag('controller.service_arguments');
};
