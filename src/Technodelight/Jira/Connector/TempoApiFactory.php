<?php

namespace Technodelight\Jira\Connector;

use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Api\Tempo\HttpClient;

class TempoApiFactory
{
    public static function build(ApplicationConfiguration $config, $instanceName = null)
    {
        $apiConfig = self::getApiConfiguration($config, $instanceName);
        return new HttpClient($apiConfig['url'], $apiConfig['user'], $apiConfig['pass']);
    }

    private static function getApiConfiguration(ApplicationConfiguration $config, $instanceName = null)
    {
        $tempo = $config->tempo();
        $tempoInstances = (array) $tempo['instances'];
        if (self::shouldUseInstanceConfig($instanceName, $tempoInstances)) {
            $instance = $config->instance($instanceName);
            return [
                'url' => self::apiUrl($instance['domain']),
                'user' => $instance['username'],
                'pass' => $instance['password'],
            ];
        }

        return [
            'url' => self::apiUrl($config->domain()),
            'user' => $config->username(),
            'pass' => $config->password()
        ];
    }

    /**
     * @param string $instanceName
     * @param array $tempoInstances
     * @return bool
     */
    private static function shouldUseInstanceConfig($instanceName, $tempoInstances)
    {
        return !empty($instanceName) && (in_array($instanceName, $tempoInstances) || empty($tempoInstances));
    }

    private static function apiUrl($projectDomain)
    {
        return sprintf('https://%s', $projectDomain);
    }
}
