<?php

namespace Technodelight\Jira\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @var TreeBuilder
     */
    private $builder;

    public function __construct(TreeBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function getConfigTreeBuilder()
    {
        return $this->builder;
    }
}
