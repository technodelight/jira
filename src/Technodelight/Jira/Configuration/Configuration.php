<?php

namespace Technodelight\Jira\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Technodelight\Jira\Configuration\Configuration\TreeBuilderFactory;

class Configuration implements ConfigurationInterface
{
    private TreeBuilder $treeBuilder;
    private ArrayNodeDefinition $rootNode;

    public function __construct(private readonly TreeBuilderFactory $builder)
    {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $this->build();

        return $this->treeBuilder;
    }

    public function getRootNode(): ArrayNodeDefinition
    {
        $this->build();

        return $this->rootNode;
    }

    private function build(): void
    {
        if (!isset($this->treeBuilder) || !isset($this->rootNode)) {
            list ($this->treeBuilder, $this->rootNode) = $this->builder->build();
        }
    }
}
