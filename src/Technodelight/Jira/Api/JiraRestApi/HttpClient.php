<?php

namespace Technodelight\Jira\Api\JiraRestApi;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Promise;
use Technodelight\Jira\Api\JiraRestApi\HttpClient\ConfigProvider;

class HttpClient implements Client
{
    const API_PATH = '/rest/api/2/';

    /**
     * @var GuzzleClient
     */
    private $httpClient;

    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\HttpClient\ConfigProvider
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
        try {
            $result = $this->httpClient()->post($url, ['json' => $data]);
            return json_decode($result->getBody(), true);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function put($url, $data = [])
    {
        try {
            $result = $this->httpClient()->put($url, ['json' => $data]);
            return json_decode($result->getBody(), true);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function get($url)
    {
        try {
            $result = $this->httpClient()->get($url);
            return json_decode($result->getBody(), true);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    public function delete($url)
    {
        try {
            $result = $this->httpClient()->delete($url);
            return json_decode($result->getBody(), true);
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
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
            /** @var \Psr\Http\Message\ResponseInterface $value */
            $value = $settle['value'];
            $results[$url] = json_decode((string) $value->getBody(), true);
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
        } catch (GuzzleClientException $exception) {
            throw ClientException::fromException($exception);
        }
    }

    public function download($url, $filename)
    {
        $this->httpClient()->get($url, ['save_to' => $filename]);
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
                    'auth' => [$this->configProvider->username(), $this->configProvider->password()],
                    'allow_redirects' => true,
                ]
            );
        }

        return $this->httpClient;
    }
}
