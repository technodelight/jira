<?php

namespace Technodelight\Jira\Extension;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
            $rootNode->append($extension->configure());
        }
    }

    public function load(array $configs, ContainerInterface $container)
    {
        foreach ($this->extensions as $extension) {
            $extension->load($configs, $container);
        }
    }
}
