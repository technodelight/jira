<?php

namespace Technodelight\Jira\Configuration\Symfony\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Aliases implements Configuration
{

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        return (new TreeBuilder)->root('aliases')
            ->info('Use named issues instead of numbers. Can be used anywhere where issueKey is a command\'s input')
            ->prototype('array')
                ->children()
                    ->scalarNode('alias')->cannotBeEmpty()->isRequired()->end()
                    ->scalarNode('issueKey')->cannotBeEmpty()->isRequired()->end()
                ->end()
            ->end();
    }
}
