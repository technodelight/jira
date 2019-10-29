<?php

namespace Technodelight\Jira\Extension;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface ExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container);

    public function configure(): ArrayNodeDefinition;
}
