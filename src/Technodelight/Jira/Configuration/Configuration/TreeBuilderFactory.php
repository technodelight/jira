<?php

namespace Technodelight\Jira\Configuration\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class TreeBuilderFactory
{
    public static function build()
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
                ->append((new Extensions)->configurations())
            ->end()
        ->end();

        return $treeBuilder;
    }
}
