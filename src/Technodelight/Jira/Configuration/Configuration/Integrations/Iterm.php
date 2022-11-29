<?php

namespace Technodelight\Jira\Configuration\Configuration\Integrations;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Technodelight\Jira\Configuration\Configuration\Configuration;

class Iterm implements Configuration
{
    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        return (new TreeBuilder('iterm'))->getRootNode()
            ->info('iTerm2 integration (OS X Only)')
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('renderImages')->defaultTrue()->treatNullLike(true)->end()
                ->scalarNode('thumbnailWidth')->defaultValue(300)->treatNullLike(300)->end()
                ->scalarNode('imageCacheTtl')->defaultValue(5)->treatNullLike(5)->end()
            ->end();
    }
}
