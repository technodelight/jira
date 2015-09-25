<?php

namespace Technodelight\Jira\Api;

use GuzzleHttp\Client as GuzzleClient;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Api\SearchResultList;
use Technodelight\Jira\Api\Issue;

class Client
{
    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->httpClient = new GuzzleClient(
            [
                'base_url' => $this->apiUrl($config->domain()),
                'defaults' => [
                    'auth' => [$config->username(), $config->password()]
                ]
            ]
        );
    }

    public function post($url, $data = [])
    {
        return $this->httpClient->post($url, ['json' => $data])->json();
    }

    public function get($url)
    {
        return $this->httpClient->get($url)->json();
    }

    /**
     * @param string $jql
     *
     * @return array
     */
    public function search($jql)
    {
        return $this->httpClient->get('search', ['query' => ['jql' => $jql]])->json();
    }

    private function apiUrl($projectDomain)
    {
        return sprintf(
            'https://%s/rest/api/2/',
            $projectDomain
        );
    }
}
