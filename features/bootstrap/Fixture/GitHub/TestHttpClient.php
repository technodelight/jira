<?php

namespace Fixture\GitHub;

use Github\HttpClient\HttpClientInterface;
use Guzzle\Http\Message\Response;

class TestHttpClient implements HttpClientInterface
{
    public $authenticated = false;

    public $requests = array(
        'get' => array(),
        'post' => array(),
        'patch' => array(),
        'put' => array(),
        'delete' => array(),
    );
    public $options = array();
    public $headers = array();

    public static $fixtures = [
        'get' => array(),
        'post' => array(),
        'patch' => array(),
        'put' => array(),
        'delete' => array(),
    ];

    public function authenticate($tokenOrLogin, $password, $authMethod)
    {
        $this->authenticated = true;
    }

    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function get($path, array $parameters = array(), array $headers = array())
    {
        $this->requests['get'][] = $path;
        $this->showRun('get', $path);
        if (isset(self::$fixtures['get'][$path])) {
            return new Response(200, ['content-type' => 'application/json'], self::$fixtures['get'][$path]);
        }
    }

    public function post($path, $body = null, array $headers = array())
    {
        $this->requests['post'][] = $path;
    }

    public function patch($path, $body = null, array $headers = array())
    {
        $this->requests['patch'][] = $path;
    }

    public function put($path, $body = null, array $headers = array())
    {
        $this->requests['put'][] = $path;
    }

    public function delete($path, $body = null, array $headers = array())
    {
        $this->requests['delete'][] = $path;
    }

    public function request($path, $body, $httpMethod = 'GET', array $headers = array())
    {
        $this->requests[$httpMethod][] = $path;
    }

    private function showRun($method, $path)
    {
        echo "github: $method $path" . PHP_EOL;
    }
}
