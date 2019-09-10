<?php

namespace Technodelight\Jira\Configuration\Configuration\Integrations;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Technodelight\Jira\Configuration\Configuration\Configuration;

class Github implements Configuration
{
    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        return (new TreeBuilder)->root('github')
            ->info('GitHub credentials - used to retrieve pull request data, including webhook statuses. Visit this page to generate a token: https://github.com/settings/tokens/new?scopes=repo&description=jira+cli+tool')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('apiToken')
                    ->attribute('hidden', true)
                    ->defaultNull()
                ->end()
                ->scalarNode('owner')
                    ->defaultNull()
                ->end()
                ->scalarNode('repo')
                    ->defaultNull()
                ->end()
            ->end();

    }
}
