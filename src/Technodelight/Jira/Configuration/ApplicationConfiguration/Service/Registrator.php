<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\Service;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Registrator
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param RegistrableConfiguration $config
     * @param string $parentPrefix
     */
    public function register(RegistrableConfiguration $config, $parentPrefix)
    {
        $reflection = new ReflectionClass($config);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC) as $method) {
            if ($method->getNumberOfParameters() > 0) {
                continue;
            }
            $methodName = $method->name;
            $childConfig = $config->$methodName();
            if ($childConfig instanceof RegistrableConfiguration) {
                $this->registerService($parentPrefix, $childConfig->servicePrefix(), $childConfig);
                $this->register(
                    $childConfig,
                    $this->prefix($parentPrefix, $childConfig->servicePrefix())
                );
            }
        }
    }

    private function registerService($parentPrefix, $servicePrefix, $config)
    {
        $this->container->set($this->prefix($parentPrefix, $servicePrefix), $config);
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
