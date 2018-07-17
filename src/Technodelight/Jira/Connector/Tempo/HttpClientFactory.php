<?php

namespace Technodelight\Jira\Connector\Tempo;

use Technodelight\Jira\Api\Tempo\HttpClient;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\TempoConfiguration;

class HttpClientFactory
{
    const REST_API_URL = 'https://api.tempo.io/rest-legacy/';

    public static function build(TempoConfiguration $configuration, $instanceName = null)
    {
        return new HttpClient(
            self::REST_API_URL, self::apiToken($configuration, $instanceName)
        );
    }

    private static function apiToken(TempoConfiguration $configuration, $instanceName)
    {
        if (empty($configuration->apiToken())) {
            return $configuration->instanceApiToken($instanceName);
        }

        return $configuration->apiToken();
    }
}
