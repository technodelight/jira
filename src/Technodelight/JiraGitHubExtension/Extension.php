<?php

namespace Technodelight\JiraGitHubExtension;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Technodelight\Jira\Extension\ExtensionInterface;

class Extension implements ExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Resources'));
        $loader->load('services.xml');

        $def = $container->getDefinition('technodelight.jira.config.integrations.github');
        $def->setArguments(
            [isset($configs['integrations']['github']) ? $configs['integrations']['github'] : []]
        );

        // add extra renderer to issue transition
        $def = $container->getDefinition('technodelight.jira.renderer.issue.transitions');
        $args = $def->getArguments();
        $args[count($args)] = new Reference('technodelight.jira.renderer.issue.github');
        $def->setArguments($args);
    }

    public function configure(ArrayNodeDefinition $rootNode)
    {
        //@TODO: this needs to be checked again to see why we don't receive the configs when we should have those
        $rootNode
            ->children()
                ->arrayNode('integrations')
                    ->children()
                        ->arrayNode('github')
                            ->info('GitHub credentials - used to retrieve pull request data, including webhook '
                                . 'statuses. Visit this page to generate a token: '
                                . 'https://github.com/settings/tokens/new?scopes=repo&description=jira+cli+tool')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('apiToken')
                                    ->attribute('hidden', true)
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('owner')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('repo')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
