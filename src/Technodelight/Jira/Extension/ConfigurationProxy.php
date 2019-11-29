<?php

namespace Technodelight\Jira\Extension;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Console\Configuration\Provider;

class ConfigurationProxy implements ConfigurationInterface
{
    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var Configurator
     */
    private $configurator;
    /**
     * @var Provider
     */
    private $provider;
    /**
     * @var ContainerInterface
     */
    private $container;
    private $loaded = false;

    public function __construct(Configuration $configuration, Configurator $configurator, Provider $provider, ContainerInterface $container)
    {
        $this->configuration = $configuration;
        $this->configurator = $configurator;
        $this->provider = $provider;
        $this->container = $container;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
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
