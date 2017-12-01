<?php

namespace Technodelight\Jira\Connector;

use Technodelight\Jira\Configuration\ApplicationConfiguration\InstancesConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\TempoConfiguration;
use Technodelight\Jira\Api\Tempo\HttpClient;

class TempoApiFactory
{
    public static function build(InstancesConfiguration $instances, $instanceName = null)
    {
        $apiConfig = self::getApiConfiguration($instances, $instanceName);
        return new HttpClient($apiConfig['url'], $apiConfig['user'], $apiConfig['pass']);
    }

    private static function getApiConfiguration(InstancesConfiguration $instances, $instanceName = null)
    {
        $instance = $instances->findByName($instanceName);
        return [
            'url' => self::apiUrl($instance->domain()),
            'user' => $instance->username(),
            'pass' => $instance->password(),
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
