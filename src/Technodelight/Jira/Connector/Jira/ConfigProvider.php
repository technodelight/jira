<?php

namespace Technodelight\Jira\Connector\Jira;

use Technodelight\Jira\Api\JiraRestApi\HttpClient\Config;
use Technodelight\Jira\Configuration\ApplicationConfiguration\CurrentInstanceProvider;

class ConfigProvider implements Config
{
    /**
     * @var CurrentInstanceProvider
     */
    private $instanceProvider;

    public function __construct(CurrentInstanceProvider $currentInstanceProvider)
    {
        $this->instanceProvider = $currentInstanceProvider;
    }

    public function username()
    {
        return $this->instanceProvider->currentInstance()->username();
    }

    public function password()
    {
        return $this->instanceProvider->currentInstance()->password();
    }

    public function domain()
    {
        return $this->instanceProvider->currentInstance()->domain();
    }
}
