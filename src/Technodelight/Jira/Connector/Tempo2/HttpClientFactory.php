<?php

namespace Technodelight\Jira\Connector\Tempo2;

use Technodelight\Jira\Api\Tempo2\HttpClient;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\TempoConfiguration;

class HttpClientFactory
{
    public static function build(TempoConfiguration $tempoConfiguration, $instanceName)
    {
        $apiToken = $tempoConfiguration->apiToken();
        if (empty($tempoConfiguration->apiToken())) {
            $apiToken = $tempoConfiguration->instanceApiToken($instanceName);
        }
        return new HttpClient($apiToken);
    }
}
