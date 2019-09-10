<?php

namespace Technodelight\Jira\Connector\Tempo2;

use Technodelight\Tempo2\HttpClient;
use Technodelight\Tempo2\NullClient;
use Technodelight\Jira\Configuration\ApplicationConfiguration\CurrentInstanceProvider;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\TempoConfiguration;

class HttpClientFactory
{
    public static function build(TempoConfiguration $tempoConfiguration, CurrentInstanceProvider $instanceProvider)
    {
        try {
            if ($tempoConfiguration->isEnabled() === false
                || (empty($tempoConfiguration->apiToken()) && empty($tempoConfiguration->instanceApiToken($instanceProvider->currentInstance()->name())))) {
                return new NullClient;
            }
        } catch (\InvalidArgumentException $e) {
            return new NullClient;
        }

        return new HttpClient(new ApiToken($tempoConfiguration, $instanceProvider));
    }
}
