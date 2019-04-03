<?php

namespace Technodelight\Jira\Api\Tempo2;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use Psr\Http\Message\ResponseInterface;

class HttpClient implements Client
{
    const REST_API_URL = 'https://api.tempo.io/core';
    const REST_API_VERSION = '3';

    /**
     * @var string
     */
    private $apiToken;
    /**
     * @var GuzzleClient
     */
    private $httpClient;

    public function __construct($apiToken)
    {
        $this->apiToken = $apiToken;
    }

    /**
     * Gets a resource
     *
     * @param string $url
     * @param array $params
     * @return array
     */
    public function get($url, array $params = [])
    {
        if (!empty($params)) {
            $url.= '?' . http_build_query($params);
        }
        return $this->httpCall('get', $url);
    }

    /**
     * Creates a resource
     *
     * @param string $url
     * @param array $data
     * @return array
     */
    public function post($url, array $data)
    {
        return $this->httpCall('post', $url, $data);
    }

    /**
     * Updates a resource
     *
     * @param string $url
     * @param array $data
     * @return array
     */
    public function put($url, array $data)
    {
        return $this->httpCall('put', $url, $data);
    }

    /**
     * Removes a resource
     *
     * @param string $url
     * @param array $params
     * @return array
     */
    public function delete($url, array $params = [])
    {
        if (!empty($params)) {
            $url.= '?' . http_build_query($params);
        }
        return $this->httpCall('delete', $url);
    }

    /**
     * @return GuzzleClient
     */
    private function httpClient()
    {
        if (!isset($this->httpClient)) {
            $this->httpClient = new GuzzleClient([
                'base_uri' => sprintf('%s/%s/', self::REST_API_URL, self::REST_API_VERSION),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken
                ],
            ]);
        }

        return $this->httpClient;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     */
    private function httpCall($method, $url, $data = [])
    {
        $path = ltrim($url, '/');
        try {
            switch ($method) {
                case 'get':
                case 'delete':
                    return $this->decodeJsonResponse(
                        $this->httpClient()->$method($path)
                    );
                case 'post':
                case 'put':
                    return $this->decodeJsonResponse(
                        $this->httpClient()->$method($path, ['json' => $data])
                    );
            }
        } catch (GuzzleClientException $e) {
            throw ClientException::fromException($e);
        }
    }

    /**
     * @param ResponseInterface $response
     * @return array
     */
    private function decodeJsonResponse(ResponseInterface $response)
    {
        return json_decode((string) $response->getBody(), true);
    }
}
