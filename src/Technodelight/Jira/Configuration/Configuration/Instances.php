<?php

namespace Technodelight\Jira\Configuration\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Instances implements Configuration
{

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        $root = (new TreeBuilder())->root('instances');

        $root
            ->info('Different JIRA instances to use')
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name', false)
            ->addDefaultChildrenIfNoneSet(['default'])
            ->prototype('array')
                ->normalizeKeys(false)
                ->children()
                    ->scalarNode('name')
                        ->info('Unique internal ID to use in command line arguments as reference (ie. --instance secondary)')
                        ->defaultValue('default')
                        ->example('secondary')
                    ->end()
                    ->scalarNode('domain')
                        ->info('JIRA host like something.atlassian.net or with proto://user:pass@host:port: http://user:pass@localhost:8080')
                        ->example('something.atlassian.net')
                        ->defaultValue('something.atlassian.net')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->scalarNode('username')
                        ->info('Instance JIRA username')
                        ->isRequired()
                        ->defaultValue('<your jira username>')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('password')
                        ->attribute('hidden', true)
                        ->info('Instance JIRA password')
                        ->defaultValue('supersecretpassword')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->booleanNode('tempo')
                        ->info('Is tempo enabled for this instance?')
                        ->defaultNull()
                        ->validate()
                            ->ifString()->then(function($value) { return (bool) $value; })
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $root;
    }
}
