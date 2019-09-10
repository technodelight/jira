<?php

namespace Technodelight\Jira\Extension;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Configurator
{
    /**
     * @var ExtensionInterface[]
     */
    private $extensions;

    public function __construct(array $extensions = [])
    {
        $this->extensions = $extensions;
    }

    public function configure(TreeBuilder $builder)
    {
        foreach ($this->extensions as $extension) {
            $extension->configure($builder);
        }
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        foreach ($this->extensions as $extension) {
            $extension->load($configs, $container);
        }
    }
}
