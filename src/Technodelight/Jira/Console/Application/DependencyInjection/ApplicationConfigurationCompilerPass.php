<?php

namespace Technodelight\Jira\Console\Application\DependencyInjection;

use Fixture\ApplicationConfiguration;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;
use Technodelight\Jira\Configuration\Symfony\ConfigurationLoader;

class ApplicationConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $this->prepareConfigurationBuilder($container);
        /** @var \Technodelight\Jira\Configuration\ApplicationConfiguration $config */
        $config = $container->get('technodelight.jira.config');

        $this->collectRegistrablesAndProcess($container, $config, $config->servicePrefix());
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param RegistrableConfiguration $config
     * @param string $parentPrefix
     * @throws \ReflectionException
     */
    private function collectRegistrablesAndProcess(ContainerBuilder $container, RegistrableConfiguration $config, $parentPrefix)
    {
        $reflection = new ReflectionClass($config);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC) as $method) {
            if ($method->getNumberOfParameters() > 0) {
                continue;
            }
            $methodName = $method->name;
            $childConfig = $config->$methodName();
            if (is_array($childConfig)) {
                foreach ($childConfig as $conf) {
                    $this->processRegistrableConfiguration($container, $parentPrefix, $conf);
                }
            } else {
                $this->processRegistrableConfiguration($container, $parentPrefix, $childConfig);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $parentPrefix
     * @param string $servicePrefix
     * @param RegistrableConfiguration $config
     */
    private function addServiceDefinition(ContainerBuilder $container, $parentPrefix, $servicePrefix, RegistrableConfiguration $config)
    {
        $definition = new Definition(
            get_class($config)
        );
        $definition->setFactory([
            get_class($config), 'fromArray'
        ]);
        $definition->setArguments([$config->configAsArray()]);

        $serviceId = $this->prefix($parentPrefix, $servicePrefix);
        $container->removeDefinition($serviceId);
        $container->setDefinition(
            $serviceId,
            $definition
        );
    }

    /**
     * @param string $parentPrefix
     * @param string $servicePrefix
     * @return string
     */
    private function prefix($parentPrefix, $servicePrefix)
    {
        return sprintf('%s.%s', $parentPrefix, $servicePrefix);
    }

    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    private function prepareConfigurationBuilder(ContainerBuilder $container)
    {
        $configDef = $container->getDefinition('technodelight.jira.config');
        if (defined('ENVIRONMENT') && ENVIRONMENT == 'test') {
            /** @var ApplicationConfiguration $fixtureConfig */
            $fixtureConfig = $container->get('fixture.jira.config');
            $configDef->setArguments([$fixtureConfig->configAsArray()]);
            return;
        }

        /** @var ConfigurationLoader $loader */
        $loader = $container->get('technodelight.jira.configuration.symfony.configuration_loader');
        $configDef->setArguments([
            $loader->load()
        ]);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $parentPrefix
     * @param mixed $childConfig
     * @throws \ReflectionException
     */
    private function processRegistrableConfiguration(ContainerBuilder $container, $parentPrefix, $childConfig)
    {
        if ($childConfig instanceof RegistrableConfiguration) {
            $this->addServiceDefinition($container, $parentPrefix, $childConfig->servicePrefix(), $childConfig);
            $this->collectRegistrablesAndProcess(
                $container,
                $childConfig,
                $this->prefix($parentPrefix, $childConfig->servicePrefix())
            );
        }
    }
}
