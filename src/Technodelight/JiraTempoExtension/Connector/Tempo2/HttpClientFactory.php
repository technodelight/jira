<?php

namespace Technodelight\JiraTempoExtension\Connector\Tempo2;

use InvalidArgumentException;
use Technodelight\Tempo2\HttpClient;
use Technodelight\Tempo2\NullClient;
use Technodelight\Jira\Configuration\ApplicationConfiguration\CurrentInstanceProvider;
use Technodelight\JiraTempoExtension\Configuration\TempoConfiguration;

class HttpClientFactory
{
    public static function build(TempoConfiguration $tempoConfiguration, CurrentInstanceProvider $instanceProvider)
    {
        try {
            if ((self::isMisconfigured($tempoConfiguration, $instanceProvider))) {
                return new NullClient;
            }
        } catch (InvalidArgumentException $e) {
            return new NullClient;
        }

        return new HttpClient(new ApiToken($tempoConfiguration, $instanceProvider));
    }

    private static function isMisconfigured(
        TempoConfiguration $tempoConfiguration,
        CurrentInstanceProvider $instanceProvider
    ): bool {
        return empty($tempoConfiguration->instanceApiToken($instanceProvider->currentInstance()->name()));
    }
}
