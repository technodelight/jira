<?php

namespace Technodelight\Jira\Configuration\Configuration\Integrations;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Technodelight\Jira\Configuration\Configuration\Configuration;

class Editor implements Configuration
{
    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        return (new TreeBuilder('editor'))->getRootNode()
            ->info('Editor preferences')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('executable')->defaultValue('vim')->end()
            ->end();
    }
}
