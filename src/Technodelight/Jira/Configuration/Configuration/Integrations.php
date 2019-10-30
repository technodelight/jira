<?php

namespace Technodelight\Jira\Configuration\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Technodelight\Jira\Configuration\Configuration\Integrations\Editor;
use Technodelight\Jira\Configuration\Configuration\Integrations\Git;
use Technodelight\Jira\Configuration\Configuration\Integrations\Iterm;

class Integrations implements Configuration
{
    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        $root = (new TreeBuilder)->root('integrations');

        $root
            ->info('Third party integration configs')
            ->addDefaultsIfNotSet()
            ->ignoreExtraKeys(false)
            ->children()
                ->append((new Git)->configurations())
                ->append((new Iterm)->configurations())
                ->append((new Editor)->configurations())
            ->end();

        return $root;
    }
}
