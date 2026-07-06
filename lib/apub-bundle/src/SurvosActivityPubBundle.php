<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle;

use Survos\Kit\AbstractSurvosBundle;
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

        // Service/ (and, once populated, MessageHandler/) aren't auto-scanned by
        // AbstractSurvosBundle — only Command/ and Controller/ are conventional.
        $container->services()
            ->defaults()->autowire()->autoconfigure()
            ->load('Survos\\ActivityPubBundle\\Service\\', $this->bundleRootPath() . '/src/Service/');

        $this->captureRouteConfig($config);
        $this->registerRouteLoader($builder);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $this->addRouteLoaderCompilerPass($container);
    }
}
