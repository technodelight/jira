<?php

namespace Technodelight\Jira\Connector\Tempo2;

use Technodelight\Jira\Api\Tempo2\HttpClient;
use Technodelight\Jira\Configuration\ApplicationConfiguration\CurrentInstanceProvider;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\TempoConfiguration;

class HttpClientFactory
{
    public static function build(TempoConfiguration $tempoConfiguration, CurrentInstanceProvider $instanceProvider)
    {
        $apiToken = $tempoConfiguration->apiToken();
        if (empty($tempoConfiguration->apiToken())) {
            $apiToken = $tempoConfiguration->instanceApiToken($instanceProvider->currentInstance()->name());
        }
        return new HttpClient($apiToken);
    }
}
