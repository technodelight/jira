<?php

namespace Technodelight\Jira\Configuration\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Transitions implements Configuration
{

    /** @return ArrayNodeDefinition|NodeDefinition */
    public function configurations(): ArrayNodeDefinition|NodeDefinition
    {
        return (new TreeBuilder('transitions'))->getRootNode()
            ->arrayPrototype()
                ->normalizeKeys(true)
                ->info('Issue transitions registered as commands')
                ->children()
                    ->scalarNode('command')->cannotBeEmpty()->isRequired()->end()
                    ->variableNode('transition')->beforeNormalization()->castToArray()->end()->end()
                ->end()
            ->end()
        ;
//        return (new TreeBuilder('transitions', 'array'))->getRootNode()
//            ->info('Issue transitions registered as commands')
//            ->arrayPrototype()
//                ->children()
//                    ->scalarNode('command')->cannotBeEmpty()->isRequired()->end()
//                    ->variableNode('transition')->beforeNormalization()->ifString()->then(function ($value) {
//                        return [$value];
//                    })->end()
//                ->end()
//            ->end();
    }
}
