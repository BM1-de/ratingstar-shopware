<?php declare(strict_types=1);

namespace RatingStar\Seal;

use Doctrine\DBAL\Connection;
use RatingStar\Seal\Service\RatingStarClient;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

/**
 * RatingStar seal and Google review stars for Shopware 6.
 *
 * Services, routes and the system configuration are wired in
 * Resources/config, the shopping experience element lives in the
 * administration bundle. Shopware removes the plugin configuration on
 * uninstall unless the user keeps the data; the shopping experience blocks
 * and elements that use the seal are removed here in the same case.
 */
class RatingStarSeal extends Plugin
{
    /**
     * Type of the shopping experience block and element (see the
     * administration constant ELEMENT_NAME).
     */
    public const CMS_TYPE = 'rs-seal';

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $connection = $this->container->get(Connection::class);

        // An element placed in a block of another type goes on its own, then
        // the seal blocks with their slots (the foreign key cascades to the
        // slots and their translations).
        $connection->executeStatement('DELETE FROM cms_slot WHERE type = :type', ['type' => self::CMS_TYPE]);
        $connection->executeStatement('DELETE FROM cms_block WHERE type = :type', ['type' => self::CMS_TYPE]);

        // Cached payloads and the pages tagged with them.
        $this->container->get(CacheInvalidator::class)->invalidate([RatingStarClient::CACHE_TAG], true);
    }
}
