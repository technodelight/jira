<?php

namespace Technodelight\JiraTempoExtension\Configuration;

use InvalidArgumentException;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class TempoConfiguration implements RegistrableConfiguration
{
    /**
     * @var array
     */
    private $instances;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->instances = $config['instances'];

        return $instance;
    }

    /**
     * @return array
     */
    public function instances()
    {
        return $this->instances;
    }

    /**
     * @param string $instanceName
     * @return string
     */
    public function instanceApiToken($instanceName)
    {
        foreach ($this->instances as $instance) {
            if ($instance['instance'] == $instanceName) {
                return $instance['apiToken'];
            }
        }

        throw new InvalidArgumentException(
            sprintf('Cannot find instance %s', $instanceName)
        );
    }

    public function servicePrefix()
    {
        return 'tempo';
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
