<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle;

use Survos\ActivityPubBundle\Repository\ActivityPubActivityRepository;
use Survos\ActivityPubBundle\Repository\ActivityPubActorRepository;
use Survos\ActivityPubBundle\Repository\ActivityPubFollowerRepository;
use Survos\ActivityPubBundle\Routing\WebFingerRouteLoader;
use Survos\Kit\AbstractSurvosBundle;
use Survos\Kit\Compiler\BundleRouteLoaderCompilerPass;
use Survos\Kit\SurvosKitBundle;
use Survos\Kit\Traits\HasConfigurableRoutes;
use Survos\Kit\Traits\HasDoctrineEntities;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

// Symfony\Component\HttpKernel\Bundle\Bundle <-- Flex auto-registration marker (see Survos\Kit\AbstractSurvosBundle)
#[RequiredBundle(SurvosKitBundle::class)]
final class SurvosActivityPubBundle extends AbstractSurvosBundle
{
    use HasDoctrineEntities;
    use HasConfigurableRoutes;

    protected function doctrineAlias(): string
    {
        return 'ActivityPub';
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $children = $definition->rootNode()->children();
        $this->addRouteOptions($children, '/ap');
        $children->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder); // auto-scans Controller/, Command/

        // Service/ and MessageHandler/ aren't auto-scanned by AbstractSurvosBundle —
        // only Command/ and Controller/ are conventional.
        $container->services()
            ->defaults()->autowire()->autoconfigure()
            ->load('Survos\\ActivityPubBundle\\Service\\', $this->bundleRootPath() . '/src/Service/');
        $container->services()
            ->defaults()->autowire()->autoconfigure()
            ->load('Survos\\ActivityPubBundle\\MessageHandler\\', $this->bundleRootPath() . '/src/MessageHandler/');

        $this->registerRepositories(
            $container,
            ActivityPubActorRepository::class,
            ActivityPubActivityRepository::class,
            ActivityPubFollowerRepository::class,
        );

        $this->captureRouteConfig($config);
        $this->registerRouteLoader($builder);

        // WebFinger (RFC 7033) lives at the fixed /.well-known/webfinger path and must
        // never be prefixed by route_prefix like the rest of Controller/ — chained via
        // its own loader/compiler pass instead of HasConfigurableRoutes' single prefix.
        $builder->register('survos_activity_pub.webfinger_route_loader', WebFingerRouteLoader::class)
            ->setArgument('$originalResource', '')
            ->addTag('routing.route_loader');
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $this->addRouteLoaderCompilerPass($container);
        $container->addCompilerPass(new BundleRouteLoaderCompilerPass('survos_activity_pub.webfinger_route_loader'));
    }
}
