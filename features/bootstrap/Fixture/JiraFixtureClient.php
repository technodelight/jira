<?php

namespace Fixture;

use Technodelight\Jira\Api\JiraRestApi\Client;

class JiraFixtureClient implements Client
{
    const FIXTURE_PATH = '../fixtures/jira/';
    const ERROR_NO_SUCH_FIXTURE = 'No such fixture: "%s" (%s)';
    const ERROR_CANNOT_WRITE_FIXTURE = 'Fixture assertion failed: "%s"';
    const ERROR_CANNOT_UNSERIALIZE_FIXTURE = "Fixture unserialization failure: \"%s\" (%s)\n%s";

    private $posts = [];
    private static $setups = ['get' => [], 'post' => [], 'put' => [], 'delete' => [], 'search' => []];

    public function post($url, $data = [])
    {
        $this->write($url, $data);
        return $this->read('post', $url);
    }

    public function put($url, $data = [])
    {
        $this->write($url, $data);
        return $this->read('put', $url);
    }

    public function get($url)
    {
        return $this->read('get', $url);
    }

    public function delete($url)
    {
        // ?
    }

    public function multiGet(array $urls)
    {
        $results = [];
        foreach ($urls as $url) {
            $results[$url] = $this->read('get', $url);
        }
        return $results;
    }

    public function search($jql, $startAt = null, $fields = null, array $expand = null, array $properties = null)
    {
        return $this->read('get', $jql);
    }

    public function download($url, $filename, callable $progressFunction = null)
    {
        // ?
    }

    public function upload($url, $filename)
    {
        // ?
    }

    public static function setup($method, $url, $response)
    {
        self::$setups[$method][$url] = $response;
    }

    private function read($method, $url)
    {
        $filename = __DIR__ . '/' . self::FIXTURE_PATH . $this->keyify($url);
        $this->debugInfo($method, $url, $filename);

        if (isset(self::$setups[$method][$url])) {
            return self::$setups[$method][$url];
        }

        if (!is_readable($filename)) {
            throw new \InvalidArgumentException(sprintf(self::ERROR_NO_SUCH_FIXTURE, $url, $this->keyify($url)));
        }
        $data = json_decode(file_get_contents($filename), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(
                sprintf(self::ERROR_CANNOT_UNSERIALIZE_FIXTURE, $url, $this->keyify($url), json_last_error_msg())
            );
        }

        return $data;
    }

    private function write($url, $data)
    {
        $this->debugInfo('POST', $url);

        $this->posts[$this->keyify($url)] = $data;
    }

    private function keyify($key)
    {
        return md5($key);
    }

    private function debugInfo($method, $url, $filename = '')
    {
        file_put_contents(
            'php://stdout',
            sprintf(
                "      \033[1;36mFixture: %s %s\033[0m (%s)" . PHP_EOL,
                $method,
                $url,
                $filename ? basename($filename) : 'no file'
            )
        );
    }
}
