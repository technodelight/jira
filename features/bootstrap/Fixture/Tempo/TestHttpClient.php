<?php

namespace Fixture\Tempo;

use Technodelight\Jira\Api\Tempo\Client;

class TestHttpClient implements Client
{
    public $authenticated = false;

    public static $requests = array(
        'get' => array(),
        'post' => array(),
        'patch' => array(),
        'put' => array(),
        'delete' => array(),
    );

    public static $fixtures = [
        'get' => array(),
        'post' => array(),
        'patch' => array(),
        'put' => array(),
        'delete' => array(),
    ];

    /**
     * Gets a resource
     *
     * @param string $url
     * @param array $params
     * @return array
     */
    public function get($url, array $params = [])
    {
        return $this->fixtureRequest('get', $url, $params);
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
        return $this->fixtureRequest('post', $url, $data);
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
        return $this->fixtureRequest('put', $url, $data);
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
        return $this->fixtureRequest('delete', $url, $params);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $params
     * @return mixed
     */
    private function fixtureRequest($method, $url, array $params)
    {
        $realUrl = $url;
        if ($method == 'get' OR $method == 'delete') {
            $realUrl = $url . '?' . http_build_query($params);
        }

        self::$requests[$method][] = ['url' => $realUrl, 'params' => $params];
        if (isset(self::$fixtures[$method][$realUrl])) {
            return self::$fixtures[$method][$realUrl];
        }
    }
}
