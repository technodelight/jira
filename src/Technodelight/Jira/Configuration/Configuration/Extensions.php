<?php

namespace Technodelight\Jira\Configuration\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Extensions implements Configuration
{
    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        $root = (new TreeBuilder)->root('extensions');

        $root
            ->info('JIRA CLI tool extensions')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('paths')
                    ->prototype('scalar')
                        ->defaultValue([
                            './tools/jira/extensions',
                            '~/.jira/extensions',
                        ])
                    ->end()
                ->end()
                ->arrayNode('class')
                    ->prototype('scalar')->isRequired()->end()
                ->end()
            ->end();

        return $root;
    }
}
