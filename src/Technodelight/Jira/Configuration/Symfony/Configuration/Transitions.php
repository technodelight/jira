<?php

namespace Technodelight\Jira\Configuration\Symfony\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Transitions implements Configuration
{

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        $root = (new TreeBuilder)->root('transitions');

        $root
            ->info('Issue transitions registered as commands')
            ->prototype('array')
                ->children()
                    ->scalarNode('command')->cannotBeEmpty()->isRequired()->end()
                    ->variableNode('transition')->beforeNormalization()->ifString()->then(function ($value) {
                        return [$value];
                    })
                ->end()
            ->end();

        return $root;
    }
}
