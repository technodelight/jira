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
    public function configurations(): ArrayNodeDefinition|NodeDefinition
    {
        $root = (new TreeBuilder('instances'))->getRootNode();

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
                    ->scalarNode('email')
                        ->info('Atlassian ID (email)')
                        ->isRequired()
                        ->defaultValue('your@atlassian.email')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('token')
                        ->attribute('hidden', true)
                        ->info('Instance API token, can be obtained from https://id.atlassian.com/manage-profile/security/api-tokens')
                        ->defaultValue('supersecrettoken')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('username')
                        ->info('Instance JIRA username (your atlassian ID)')
                        ->setDeprecated('technodelight/jira', '0.20.8', 'Use the email key instead')
                    ->end()
                    ->scalarNode('password')
                        ->attribute('hidden', true)
                        ->setDeprecated('technodelight/jira', '0.20.8', 'Use the token key instead')
                    ->end()
                    ->scalarNode('worklogHandler')
                        ->info('Name of the worklog handler, defaults to jira\'s internal one (default)')
                        ->defaultValue('default')
                    ->end()
                ->end()
            ->end()
        ->end();

        return $root;
    }
}
