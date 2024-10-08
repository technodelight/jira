<?php

declare(strict_types=1);

namespace Technodelight\Jira\Configuration\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Extensions implements Configuration
{
    public function configurations(): ArrayNodeDefinition|NodeDefinition
    {
        $root = (new TreeBuilder('extensions'))->getRootNode();

        $root
            ->info('JIRA CLI tool extensions')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('paths')
                    ->prototype('scalar')
                        ->defaultValue([
                            './tools/jira/extensions',
                            '~/.config/jira/extensions',
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
