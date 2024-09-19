<?php

declare(strict_types=1);

namespace Technodelight\Jira\Configuration\Configuration\Integrations;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Technodelight\Jira\Configuration\Configuration\Configuration;

class Editor implements Configuration
{
    public function configurations(): ArrayNodeDefinition|NodeDefinition
    {
        return (new TreeBuilder('editor'))->getRootNode()
            ->info('Editor preferences')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('executable')->defaultValue('vim')->end()
            ->end();
    }
}
