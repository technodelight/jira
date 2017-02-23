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
        $this->debugStat  = new DebugStat;
    }

    public function post($url, $data = [])
    {
        $this->debugStat->start('post', $url, $data);
        $result = $this->httpClient->post($url, ['json' => $data]);
        $this->debugStat->stop();
        return json_decode($result->getBody(), true);
    }

    public function put($url, $data = [])
    {
        $this->debugStat->start('put', $url, $data);
        $result = $this->httpClient->put($url, ['json' => $data]);
        $this->debugStat->stop();
        return json_decode($result->getBody(), true);
    }

    public function get($url)
    {
        $this->debugStat->start('get', $url);
        $result = $this->httpClient->get($url);
        $this->debugStat->stop();
        return json_decode($result->getBody(), true);
    }

    public function delete($url)
    {
        $this->debugStat->start('delete', $url);
        $result = $this->httpClient->delete($url);
        $this->debugStat->stop();
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
    public function search($jql, $fields = null, $expand = null)
    {
        $this->debugStat->start('search', $jql);
        $result = $this->httpClient->get(
            sprintf(
                'search%s',
                $fields || $expand ? '?' . http_build_query(
                    array_filter([
                        'fields' => $fields,
                        'expand' => $expand
                    ])
                ) : ''
            ),
            ['query' => ['jql' => $jql]]
        );
        $this->debugStat->stop();
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
