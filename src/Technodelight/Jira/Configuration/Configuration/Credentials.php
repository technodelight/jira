<?php

namespace Technodelight\Jira\Configuration\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Credentials implements Configuration
{
    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        return (new TreeBuilder)->root('credentials')
            ->info('JIRA connection credentials')
            ->attribute('deprecated', true)
            ->children()
                ->scalarNode('domain')
                    ->info('JIRA\'s domain without protocol, like something.atlassian.net')
                    ->example('something.atlassian.net')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('username')
                    ->info('Your JIRA username')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('password')
                    ->info('Your JIRA password')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end();
    }
}
