<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Routing;

use Survos\ActivityPubBundle\Controller\WebFingerController;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Registers WebFinger at the fixed /.well-known/webfinger path, independent of
 * route_prefix — chained onto the router.resource stack the same way
 * Survos\Kit\Routing\BundleRouteLoader is, via a second
 * BundleRouteLoaderCompilerPass (see SurvosActivityPubBundle::build()).
 */
final class WebFingerRouteLoader
{
    public function __construct(private string $originalResource)
    {
    }

    public function __invoke(LoaderInterface $loader, ?string $_env): RouteCollection
    {
        $collection = $loader->load($this->originalResource);

        $collection->add('survos_activity_pub_webfinger', new Route(
            path: '/.well-known/webfinger',
            defaults: ['_controller' => WebFingerController::class . '::resolve'],
            methods: ['GET'],
        ));

        return $collection;
    }
}
