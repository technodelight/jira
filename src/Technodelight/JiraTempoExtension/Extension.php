<?php

namespace Technodelight\JiraTempoExtension;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Technodelight\Jira\Extension\ExtensionInterface;

class Extension implements ExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Resources'));
        $loader->load('services.xml');

        $def = $container->getDefinition('technodelight.jira.config.integrations.tempo');
        $def->setArguments(
            [isset($configs['tempo']) ? $configs['tempo'] : ['instances' => []]]
        );
    }

    public function configure(): ArrayNodeDefinition
    {
        $builder = new TreeBuilder;
        $node = $builder->root('tempo');

        $node
            ->info('Tempo timesheets (https://tempo.io/doc/timesheets/api/rest/latest)')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('instances')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('instance')->defaultNull()->end()
                        ->scalarNode('apiToken')
                            ->defaultNull()
                            ->validate()
                                ->ifEmpty()->thenInvalid('apiToken must be provided for Tempo')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
