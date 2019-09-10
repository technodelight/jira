<?php

namespace Technodelight\Jira\Console\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor as ConfigProcessor;

class Processor
{
    public function process(TreeBuilder $treeBuilder, array $configs)
    {
        return (new ConfigProcessor)->process(
            $treeBuilder->buildTree(),
            array_filter($configs)
        );
    }
}
