<?php

namespace Technodelight\Jira\Connector\Tempo;

use Technodelight\Jira\Api\Tempo\HttpClient;
use Technodelight\Jira\Configuration\ApplicationConfiguration\CurrentInstanceProvider;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\TempoConfiguration;

class HttpClientFactory
{
    const REST_API_URL = 'https://api.tempo.io/rest-legacy/';

    public static function build(TempoConfiguration $configuration, CurrentInstanceProvider $instanceProvider)
    {
        return new HttpClient(
            self::REST_API_URL,
            new ApiToken($configuration, $instanceProvider)
        );
    }
}
