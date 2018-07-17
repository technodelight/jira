<?php

namespace Technodelight\Jira\Connector\Jira;

use Technodelight\Jira\Api\JiraRestApi\HttpClient\Config;
use Technodelight\Jira\Configuration\ApplicationConfiguration\InstanceConfiguration;

class ConfigProvider implements Config
{
    /**
     * @var InstanceConfiguration
     */
    private $config;

    public function __construct(InstanceConfiguration $currentInstanceConfig)
    {
        $this->config = $currentInstanceConfig;
    }

    public function username()
    {
        return $this->config->username();
    }

    public function password()
    {
        return $this->config->password();
    }

    public function domain()
    {
        return $this->config->domain();
    }
}
