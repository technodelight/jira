<?php

namespace Fixture\GitHub;

use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
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
        return $this->request($path, '', 'get');
    }

    public function post($path, $body = null, array $headers = array())
    {
        return $this->request($path, $body, 'post');
    }

    public function patch($path, $body = null, array $headers = array())
    {
        return $this->request($path, '', 'patch');
    }

    public function put($path, $body = null, array $headers = array())
    {
        return $this->request($path, '', 'put');
    }

    public function delete($path, $body = null, array $headers = array())
    {
        return $this->request($path, '', 'delete');
    }

    public function request(string $path, string $body, string $httpMethod = 'get', array $headers = array())
    {
        $this->requests[$httpMethod][] = $path;
        if (isset(self::$fixtures[$httpMethod][$path])) {
            return new Response(200, ['content-type' => 'application/json'], self::$fixtures[$httpMethod][$path]);
        }

        return new Response(200);
    }

    /**
     * Sends a PSR-7 request.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \Exception             If processing the request is impossible (eg. bad configuration).
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return match ($request->getMethod()) {
            'GET' => $this->get($request->getUri()->getPath()),
            'POST' => $this->post($request->getUri()->getPath()),
            'PUT' => $this->put($request->getUri()->getPath()),
            'DELETE' => $this->delete($request->getUri()->getPath()),
            default => new Response(400),
        };
    }
}
