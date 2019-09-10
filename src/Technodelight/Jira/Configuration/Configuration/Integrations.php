<?php

namespace Technodelight\Jira\Configuration\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Technodelight\Jira\Configuration\Configuration\Integrations\Editor;
use Technodelight\Jira\Configuration\Configuration\Integrations\Git;
use Technodelight\Jira\Configuration\Configuration\Integrations\Github;
use Technodelight\Jira\Configuration\Configuration\Integrations\Iterm;
use Technodelight\Jira\Configuration\Configuration\Integrations\Tempo;

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
            ->children()
                ->append((new Github)->configurations())
                ->append((new Git)->configurations())
                ->append((new Tempo)->configurations())
                ->append((new Iterm)->configurations())
                ->append((new Editor)->configurations())
            ->end();

        return $root;
    }
}
