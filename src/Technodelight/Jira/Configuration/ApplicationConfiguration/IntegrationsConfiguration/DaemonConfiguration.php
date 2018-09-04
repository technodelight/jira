<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class DaemonConfiguration implements RegistrableConfiguration
{
    /**
     * @var bool
     */
    private $enabled;
    /**
     * @var string
     */
    private $address;
    /**
     * @var int
     */
    private $port;
    /**
     * @var array
     */
    private $config;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->config = $config;
        $instance->enabled = $config['enabled'];
        $instance->address = $config['address'];
        $instance->port = $config['port'];

        return $instance;
    }

    public function enabled()
    {
        return $this->enabled;
    }

    public function address()
    {
        return $this->address;
    }

    public function port()
    {
        return $this->port;
    }

    public function servicePrefix()
    {
        return 'daemon';
    }

    /**
     * @return array
     */
    public function configAsArray()
    {
        return $this->config;
    }
}
