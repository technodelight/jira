<?php

namespace Technodelight\Jira\Extension;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Configurator
{
    /**
     * @var ExtensionInterface[]
     */
    private $extensions;

    public function __construct(Loader $loader, array $classMap = [])
    {
        $this->extensions = $loader->load($classMap);
    }

    public function configure(ArrayNodeDefinition $rootNode)
    {
        foreach ($this->extensions as $extension) {
            $extension->configure($rootNode);
        }
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        foreach ($this->extensions as $extension) {
            $extension->load($configs, $container);
        }
    }
}
