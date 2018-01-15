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

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->enabled = (bool) $config['enabled'];
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

    public function servicePrefix()
    {
        return 'tempo';
    }

    private function __construct()
    {
    }
}
