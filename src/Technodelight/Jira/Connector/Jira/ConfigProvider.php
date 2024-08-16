<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\Jira;

use Technodelight\Jira\Api\JiraRestApi\HttpClient\Config;
use Technodelight\Jira\Configuration\ApplicationConfiguration\CurrentInstanceProvider;

class ConfigProvider implements Config
{
    public function __construct(private readonly CurrentInstanceProvider $instanceProvider) {}

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
