<?php declare(strict_types=1);

namespace RatingStar\Seal;

use Shopware\Core\Framework\Plugin;

// Services, system config and subscribers are wired via
// src/Resources/config/{services,config}.xml — no plugin lifecycle logic
// needed yet. Storefront seal block + Google-stars JSON-LD land next.
class RatingStarSeal extends Plugin
{
}
