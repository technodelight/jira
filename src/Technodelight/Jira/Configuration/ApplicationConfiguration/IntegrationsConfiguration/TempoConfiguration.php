<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class TempoConfiguration implements RegistrableConfiguration
{
    /**
     * @var bool
     */
    private $enabled;
    /**
     * @var array
     */
    private $instances;
    /**
     * @var null|string
     */
    private $version;
    /**
     * @var null|string
     */
    private $apiToken;
    /**
     * @var array
     */
    private $config;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->config = $config;
        $instance->enabled = (bool) $config['enabled'];
        $instance->version = $config['version']; // can be: null or string
        $instance->apiToken = $config['apiToken']; // can be: null or string
        if ($instance->version == '2' && empty($instance->apiToken) && empty($config['instances'])) {
            throw new \InvalidArgumentException(
                'Tempo2: you must provide an API token to use this feature'
            );
        }
        foreach ((array) $config['instances'] as $idx => $inst) {
            if ((empty($inst['name']) || empty($inst['apiToken'])) && $instance->version == '2') {
                throw new \InvalidArgumentException(
                    'Tempo2: you must provide an API token for instance '.(empty($inst['name']) ? $idx : $inst['name']).' to use this feature'
                );
            }
            $instance->instances[] = $inst;
        }
        $instance->instances = (array) $config['instances']; // can be: null, array or string

        return $instance;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
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
     * @return bool
     */
    public function instanceIsEnabled($instanceName)
    {
        foreach ($this->instances as $instance) {
            if ($instance['name'] == $instanceName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $instanceName
     * @return string
     */
    public function instanceApiToken($instanceName)
    {
        foreach ($this->instances as $instance) {
            if ($instance['name'] == $instanceName) {
                return $instance['apiToken'];
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Cannot find instance %s', $instanceName)
        );
    }

    /**
     * @return null|string
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @return null|string
     */
    public function apiToken()
    {
        return $this->apiToken;
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
