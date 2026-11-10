<?php declare(strict_types=1);

namespace RatingStar\Seal;

use Shopware\Core\Framework\Plugin;

/**
 * RatingStar seal and Google review stars for Shopware 6.
 *
 * No lifecycle logic: services, routes and the system configuration are
 * wired in Resources/config, the shopping experience element lives in the
 * administration bundle, and Shopware removes the plugin configuration on
 * uninstall unless the user keeps the data.
 */
class RatingStarSeal extends Plugin
{
}
