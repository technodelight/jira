<?php

namespace Technodelight\Jira\Api\Tempo;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use Psr\Http\Message\ResponseInterface;

class HttpClient implements Client
{
    const REST_API_ENDPOINT_PATH = '/rest/tempo-timesheets/3';

    /**
     * @var string
     */
    private $jiraUrl;
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $pass;
    /**
     * @var GuzzleClient
     */
    private $httpClient;

    public function __construct($jiraUrl, $username, $pass)
    {
        $this->jiraUrl = $jiraUrl;
        $this->username = $username;
        $this->pass = $pass;
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
                'base_uri' => rtrim($this->jiraUrl, '/') . self::REST_API_ENDPOINT_PATH,
                'auth' => [$this->username, $this->pass]
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
            throw new ClientException(
                sprintf('Error during %S API call' , strtoupper($method)), 0, $e
            );
        }
    }

    /**
     * @param ResponseInterface $response
     * @return array
     */
    private function decodeJsonResponse(ResponseInterface $response)
    {
        return json_decode($response->getBody(), true);
    }
}
