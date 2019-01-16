<?php

namespace Fixture\GitHub;

use Http\Client\HttpClient;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TestHttpClient implements HttpClient
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
        if (isset(self::$fixtures['get'][$path])) {
            return new Response(200, ['content-type' => 'application/json'], self::$fixtures['get'][$path]);
        }

        return new Response(400, [], sprintf('%s: no fixture has been found for %s', __CLASS__, $path));
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

    /**
     * Sends a PSR-7 request.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \Http\Client\Exception If an error happens during processing the request.
     * @throws \Exception             If processing the request is impossible (eg. bad configuration).
     */
    public function sendRequest(RequestInterface $request)
    {
        switch ($request->getMethod()) {
            case 'GET':
                return $this->get($request->getUri()->getPath());
            case 'POST':
                return $this->post($request->getUri()->getPath());
            case 'PUT':
                return $this->put($request->getUri()->getPath());
            case 'DELETE':
                return $this->delete($request->getUri()->getPath());
            default:
                return new Response(400);
        }
    }
}
