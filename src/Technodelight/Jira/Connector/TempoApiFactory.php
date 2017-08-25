<?php

namespace Technodelight\Technodelight\Jira\Connector;

use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Api\Tempo\HttpClient;

class TempoApiFactory
{
    public function build(ApplicationConfiguration $config)
    {
        return new HttpClient(
            $this->apiUrl($config->domain()),
            $config->username(),
            $config->password()
        );
    }

    private function apiUrl($projectDomain)
    {
        return sprintf('https://%s', $projectDomain);
    }
}
