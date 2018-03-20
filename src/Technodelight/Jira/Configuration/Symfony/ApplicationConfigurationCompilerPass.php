<?php

namespace Technodelight\Jira\Configuration\Symfony;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class ApplicationConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        $config = $container->get('technodelight.jira.config');
        $this->register($container, $config, $config->servicePrefix());
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param RegistrableConfiguration $config
     * @param string $parentPrefix
     * @throws \ReflectionException
     */
    private function register(ContainerBuilder $container, RegistrableConfiguration $config, $parentPrefix)
    {
        $reflection = new ReflectionClass($config);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC) as $method) {
            if ($method->getNumberOfParameters() > 0) {
                continue;
            }
            $methodName = $method->name;
            $childConfig = $config->$methodName();
            if ($childConfig instanceof RegistrableConfiguration) {
                $this->registerService($container, $parentPrefix, $childConfig->servicePrefix(), $childConfig);
                $this->register(
                    $container,
                    $childConfig,
                    $this->prefix($parentPrefix, $childConfig->servicePrefix())
                );
            }
        }
    }

    private function registerService(ContainerBuilder $container, $parentPrefix, $servicePrefix, $config)
    {
        $container->set($this->prefix($parentPrefix, $servicePrefix), $config);
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
}
