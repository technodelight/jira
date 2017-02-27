<?php

namespace Technodelight\Jira\Api;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise;
use Technodelight\Jira\Configuration\ApplicationConfiguration;

class HttpClient implements Client
{
    const API_PATH = '/rest/api/2/';

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var ApplicationConfiguration
     */
    private $configuration;

    /**
     * @param ApplicationConfiguration $config
     */
    public function __construct(ApplicationConfiguration $configuration)
    {
        $this->configuration = $configuration;
        $this->httpClient = new GuzzleClient(
            [
                'base_uri' => $this->apiUrl($configuration->domain()),
                'auth' => [$configuration->username(), $configuration->password()]
            ]
        );
    }

    public function post($url, $data = [])
    {
        $result = $this->httpClient->post($url, ['json' => $data]);
        return json_decode($result->getBody(), true);
    }

    public function put($url, $data = [])
    {
        $result = $this->httpClient->put($url, ['json' => $data]);
        return json_decode($result->getBody(), true);
    }

    public function get($url)
    {
        $result = $this->httpClient->get($url);
        return json_decode($result->getBody(), true);
    }

    public function delete($url)
    {
        $result = $this->httpClient->delete($url);
        return json_decode($result->getBody(), true);
    }

    public function multiGet(array $urls)
    {
        $promises = [];
        foreach ($urls as $url) {
            $promises[$url] = $this->httpClient->getAsync($url);
        }

        $responses = Promise\settle($promises)->wait();
        $results = [];
        foreach ($responses as $url => $settle) {
            if ($settle['state'] != 'fulfilled') {
                throw new \UnexpectedValueException('Something went wrong while querying JIRA!');
            }
            $results[$url] = json_decode((string) $settle['value']->getBody(), true);
        }

        return $results;
    }

    /**
     * @param string $jql
     * @param string|null $fields
     *
     * @return array
     */
    public function search($jql, $fields = null, array $expand = null, array $properties = null)
    {
        $result = $this->httpClient->post(
            sprintf(
                'search%s',
                $fields || $expand ? '?' . http_build_query(
                    array_filter([
                        'fields' => $fields,
                        'expand' => $expand ? join(',', $expand) : null,
                        'properties' => $properties ? join(',', $properties) : null
                    ])
                ) : ''
            ),
            ['json' => ['jql' => $jql]]
        );

        return json_decode($result->getBody(), true);
    }

    private function apiUrl($projectDomain)
    {
        return sprintf(
            'https://%s%s',
            $projectDomain,
            self::API_PATH
        );
    }
}
