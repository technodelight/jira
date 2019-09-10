<?php

namespace Technodelight\Jira\Configuration\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

interface Configuration
{
    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations();
}
