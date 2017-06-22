<?php

namespace Technodelight\Jira\Api;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise;
use Technodelight\Jira\Api\HttpClient\ConfigProvider;

class HttpClient implements Client
{
    const API_PATH = '/rest/api/2/';

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var \Technodelight\Jira\Api\HttpClient\ConfigProvider
     */
    private $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    public function post($url, $data = [])
    {
        $result = $this->httpClient()->post($url, ['json' => $data]);
        return json_decode($result->getBody(), true);
    }

    public function put($url, $data = [])
    {
        $result = $this->httpClient()->put($url, ['json' => $data]);
        return json_decode($result->getBody(), true);
    }

    public function get($url)
    {
        $result = $this->httpClient()->get($url);
        return json_decode($result->getBody(), true);
    }

    public function delete($url)
    {
        $result = $this->httpClient()->delete($url);
        return json_decode($result->getBody(), true);
    }

    public function multiGet(array $urls)
    {
        $promises = [];
        foreach ($urls as $url) {
            $promises[$url] = $this->httpClient()->getAsync($url);
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
        try {
            $result = $this->httpClient()->post(
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
        } catch (ClientException $exception) {
            // extract JQL error message
            $message = $exception->getMessage();
            if ($arrayResponse = json_decode($exception->getResponse()->getBody(), true)) {
                $message = isset($arrayResponse['errorMessages']) ? join(',', $arrayResponse['errorMessages']) : $message;
            }
            throw new \BadMethodCallException($message);
        }
    }

    private function apiUrl($projectDomain)
    {
        return sprintf(
            'https://%s%s',
            $projectDomain,
            self::API_PATH
        );
    }

    /**
     * @return GuzzleClient
     */
    private function httpClient()
    {
        if (!isset($this->httpClient)) {
            $this->httpClient = new GuzzleClient(
                [
                    'base_uri' => $this->apiUrl($this->configProvider->domain()),
                    'auth' => [$this->configProvider->username(), $this->configProvider->password()]
                ]
            );
        }

        return $this->httpClient;
    }
}
