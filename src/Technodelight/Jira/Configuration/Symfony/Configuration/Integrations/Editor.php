<?php

namespace Technodelight\Jira\Configuration\Symfony\Configuration\Integrations;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Technodelight\Jira\Configuration\Symfony\Configuration\Configuration;

class Editor implements Configuration
{
    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        return (new TreeBuilder)->root('editor')
            ->info('Editor preferences')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('executable')->defaultValue('vim')->end()
            ->end();
    }
}
