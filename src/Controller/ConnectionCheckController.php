<?php declare(strict_types=1);

namespace RatingStar\Seal\Controller;

use RatingStar\Seal\Service\ConnectionChecker;
use Shopware\Core\Framework\Routing\ApiRouteScope;
use Shopware\Core\PlatformRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Admin API endpoint behind the "check connection" button of the plugin
 * configuration. It checks the values currently in the form (saved or not)
 * and changes nothing.
 *
 * Restricted to users who may write the system configuration: they could
 * save the same origin anyway, so the endpoint gives no one a new way to
 * make the shop server call a host.
 */
#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [ApiRouteScope::ID]])]
class ConnectionCheckController
{
    public function __construct(private readonly ConnectionChecker $checker)
    {
    }

    #[Route(
        path: '/api/_action/rating-star-seal/check-connection',
        name: 'api.action.rating_star_seal.check_connection',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => ['system_config:update']],
        methods: [Request::METHOD_POST],
    )]
    public function check(Request $request): JsonResponse
    {
        $value = static function (string $name) use ($request): string {
            $raw = $request->request->get($name);

            return \is_scalar($raw) ? (string) $raw : '';
        };

        return new JsonResponse($this->checker->check(
            $value('baseOrigin'),
            $value('slug'),
            $value('group'),
            $value('apiKey'),
        ));
    }
}
