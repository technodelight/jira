<?php

namespace Technodelight\Jira\Api;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Pool;
use Technodelight\Jira\Configuration\Configuration;

class HttpClient implements Client
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
        $this->debugStat  = new DebugStat;
    }

    public function post($url, $data = [])
    {
        $this->debugStat->start('post', $url, $data);
        $result = $this->httpClient->post($url, ['json' => $data])->json();
        $this->debugStat->stop();
        return $result;
    }

    public function put($url, $data = [])
    {
        $this->debugStat->start('put', $url, $data);
        $result = $this->httpClient->put($url, ['json' => $data])->json();
        $this->debugStat->stop();
        return $result;
    }

    public function get($url)
    {
        $this->debugStat->start('get', $url);
        $result = $this->httpClient->get($url)->json();
        $this->debugStat->stop();
        return $result;
    }

    public function delete($url)
    {
        $this->debugStat->start('delete', $url);
        $result = $this->httpClient->delete($url)->json();
        $this->debugStat->stop();
        return $result;
    }

    public function multiGet(array $urls)
    {
        $debugStats = [];
        $requests = [];
        foreach ($urls as $url) {
            $debugStats[$url] = new DebugStat(false);
            $request = $this->httpClient->createRequest('GET', $url);
            $request->getEmitter()->on('before', function(BeforeEvent $e) use($url, $debugStats) {
                $debugStats[$url]->start('multiGet', $url);
            });
            $request->getEmitter()->on('complete', function(CompleteEvent $e) use ($url, $debugStats) {
                $debugStats[$url]->stop();
            });
            $requests[] = $request;
        }

        // Results is a GuzzleHttp\BatchResults object.
        $results = Pool::batch($this->httpClient, $requests);

        $resultArray = [];
        // Retrieve all successful responses
        foreach ($results->getSuccessful() as $response) {
            $resultArray[$response->getEffectiveUrl()] = $response->json();
        }
        // Measure calls
        foreach ($debugStats as $debugStat) {
            $this->debugStat->merge($debugStat);
        }
        return $resultArray;
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
        )->json();
        $this->debugStat->stop();
        return $result;
    }

    private function apiUrl($projectDomain)
    {
        return sprintf(
            'https://%s/rest/api/2/',
            $projectDomain
        );
    }
}
