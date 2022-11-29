<?php

namespace Technodelight\Jira\Configuration\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Renderers implements Configuration
{
    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        return (new TreeBuilder('renderers'))->getRootNode()
            ->info('Rendering setup')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('preference')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('list')
                            ->info('Default view mode for lists')
                            ->defaultValue('short')
                        ->end()
                        ->scalarNode('view')
                            ->info('Default view mode for a single issue')
                            ->defaultValue('full')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('modes')
                    ->useAttributeAsKey('name', false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')
                                ->cannotBeEmpty()
                                ->isRequired()
                                ->validate()
                                    ->ifString()->then(function ($value) {
                                        return strtolower(strtr($value, [' ' => '-']));
                                    })
                                ->end()
                            ->end()
                            ->booleanNode('inherit')->defaultTrue()->end()
                            ->arrayNode('fields')
                                ->info('see available fields in show:renderers command')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('name')->cannotBeEmpty()->isRequired()->end()
                                        ->scalarNode('formatter')->defaultValue('default')->treatNullLike('default')->end()
                                        ->booleanNode('inline')->defaultFalse()->treatNullLike(false)->end()
                                        ->scalarNode('after')->defaultValue(null)->end()
                                        ->scalarNode('before')->defaultValue(null)->end()
                                        ->booleanNode('remove')->defaultNull()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('formatters')
                    ->info('Custom formatters')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->info('Alias, as it will be used in renderer configs')->cannotBeEmpty()->isRequired()->end()
                            ->scalarNode('class')->info('Full class path with namespace')->cannotBeEmpty()->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
