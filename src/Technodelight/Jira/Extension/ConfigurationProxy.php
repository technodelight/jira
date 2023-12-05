<?php

namespace Technodelight\Jira\Extension;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Console\Configuration\Provider;

class ConfigurationProxy implements ConfigurationInterface
{
    private bool $loaded = false;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly Configurator $configurator,
        private readonly Provider $provider,
        private readonly ContainerInterface $container
    ) {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $result = $this->configuration->getConfigTreeBuilder();
        $this->processExtensions();

        return $result;
    }

    public function __call($name, $arguments)
    {
        $result = call_user_func_array([$this->configuration, $name], $arguments);
        $this->processExtensions();

        return $result;
    }

    protected function processExtensions(): void
    {
        if (!$this->loaded) {
            $this->configurator->configure($this->configuration->getRootNode());
            $config = (new Processor)->process(
                $this->configuration->getConfigTreeBuilder()->buildTree(), $this->provider->get()
            );
            $this->configurator->load($config, $this->container);
            $this->loaded = true;
        }
    }
}
