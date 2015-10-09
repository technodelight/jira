<?php

namespace Technodelight\Jira\Api;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Pool;
use Technodelight\Jira\Api\Issue;
use Technodelight\Jira\Api\SearchResultList;
use Technodelight\Jira\Configuration\Configuration;

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

    public function multiGet(array $urls)
    {
        $requests = [];
        foreach ($urls as $url) {
            $requests[] = $this->httpClient->createRequest('GET', $url);
        }

        // Results is a GuzzleHttp\BatchResults object.
        $results = Pool::batch($this->httpClient, $requests);

        $resultArray = [];
        // Retrieve all successful responses
        foreach ($results->getSuccessful() as $response) {
            $resultArray[$response->getEffectiveUrl()] = $response->json();
        }

        return $resultArray;
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
