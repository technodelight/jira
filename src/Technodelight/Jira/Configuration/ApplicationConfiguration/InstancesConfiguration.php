<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;
use UnexpectedValueException;

/** @SuppressWarnings(PHPMD.StaticAccess,PHPMD.UnusedPrivateField) */
class InstancesConfiguration implements RegistrableConfiguration
{
    /** @var InstanceConfiguration[] */
    private array $instances;
    private array $config;

    public static function fromArray(array $config): InstancesConfiguration
    {
        $instance = new self;
        $instance->config = $config;
        $instance->instances = array_map(
            function (array $instance) {
                return InstanceConfiguration::fromArray($instance);
            },
            $config
        );

        return $instance;
    }

    /**
     * @return InstanceConfiguration[]
     */
    public function items(): array
    {
        return $this->instances;
    }

    public function findByName(string $name): InstanceConfiguration
    {
        if (count($this->items()) == 1 && $name == 'default') {
            foreach ($this->items() as $instanceConfig) {
                return $instanceConfig;
            }
        }

        foreach ($this->items() as $instance) {
            if ($instance->name() == $name) {
                return $instance;
            }
        }

        throw new UnexpectedValueException(
            sprintf('No such instance: "%s"', $name)
        );
    }

    public function servicePrefix(): string
    {
        return 'instances';
    }

    /**
     * @return array
     */
    public function configAsArray(): array
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
