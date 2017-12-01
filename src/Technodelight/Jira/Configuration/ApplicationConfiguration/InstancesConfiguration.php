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

    public static function fromArray(array $config)
    {
        $instance = new self;
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

    private function __construct()
    {
    }
}
