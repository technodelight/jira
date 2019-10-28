<?php

namespace Technodelight\Jira\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Technodelight\Jira\Configuration\Configuration\TreeBuilderFactory;

class Configuration implements ConfigurationInterface
{
    /**
     * @var TreeBuilderFactory
     */
    private $builder;
    /**
     * @var TreeBuilder
     */
    private $treeBuilder;
    /**
     * @var ArrayNodeDefinition
     */
    private $rootNode;

    public function __construct(TreeBuilderFactory $builder)
    {
        $this->builder = $builder;
    }

    public function getConfigTreeBuilder()
    {
        $this->build();

        return $this->treeBuilder;
    }

    public function getRootNode()
    {
        $this->build();

        return $this->rootNode;
    }

    private function build()
    {
        list ($this->treeBuilder, $this->rootNode) = $this->builder->build();
    }
}
