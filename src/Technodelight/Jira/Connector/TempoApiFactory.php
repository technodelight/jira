<?php

namespace Technodelight\Jira\Connector;

use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Api\Tempo\HttpClient;

class TempoApiFactory
{
    public static function build(ApplicationConfiguration $config)
    {
        return new HttpClient(
            self::apiUrl($config->domain()),
            $config->username(),
            $config->password()
        );
    }

    private static function apiUrl($projectDomain)
    {
        return sprintf('https://%s', $projectDomain);
    }
}
