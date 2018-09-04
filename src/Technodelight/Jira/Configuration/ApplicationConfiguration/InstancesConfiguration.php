<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;
use UnexpectedValueException;

class InstancesConfiguration implements RegistrableConfiguration
{
    /**
     * @var InstanceConfiguration[]
     */
    private $instances;
    /**
     * @var array
     */
    private $config;

    public static function fromArray(array $config)
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
    public function items()
    {
        return $this->instances;
    }

    /**
     * @param string $name
     * @throws UnexpectedValueException
     * @return InstanceConfiguration
     */
    public function findByName($name)
    {
        if (count($this->items()) == 1 && $name == 'default') {
            foreach ($this->items() as $instanceConfiguration) {
                return $instanceConfiguration;
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

    public function servicePrefix()
    {
        return 'instances';
    }

    /**
     * @return array
     */
    public function configAsArray()
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
