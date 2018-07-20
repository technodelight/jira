<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Technodelight\Jira\Configuration\Symfony\Configuration\Aliases;
use Technodelight\Jira\Configuration\Symfony\Configuration\Credentials;
use Technodelight\Jira\Configuration\Symfony\Configuration\Filters;
use Technodelight\Jira\Configuration\Symfony\Configuration\Instances;
use Technodelight\Jira\Configuration\Symfony\Configuration\Integrations;
use Technodelight\Jira\Configuration\Symfony\Configuration\Project;
use Technodelight\Jira\Configuration\Symfony\Configuration\Renderers;
use Technodelight\Jira\Configuration\Symfony\Configuration\Transitions;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder;

        $treeBuilder->root('')
            ->children()
                ->append((new Credentials)->configurations())
                ->append((new Instances)->configurations())
                ->append((new Integrations)->configurations())
                ->append((new Project)->configurations())
                ->append((new Transitions)->configurations())
                ->append((new Aliases)->configurations())
                ->append((new Filters)->configurations())
                ->append((new Renderers)->configurations())
            ->end()
        ->end();

        return $treeBuilder;
    }
}
