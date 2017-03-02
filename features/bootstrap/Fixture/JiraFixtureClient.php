<?php

namespace Fixture;

use Technodelight\Jira\Api\Client;

class JiraFixtureClient implements Client
{
    const ERROR_NO_SUCH_FIXTURE = 'No such fixture: "%s"';
    const ERROR_CANNOT_WRITE_FIXTURE = 'Fixture assertion failed: "%s"';
    const ERROR_CANNOT_UNSERIALIZE_FIXTURE = 'Fixture unserialization failure: "%s"';

    private $posts = [];

    public function post($url, $data = [])
    {
        $this->write($url, $data);
        return $this->read($url);
    }

    public function put($url, $data = [])
    {
        $this->write($url, $data);
        return $this->read($url);
    }

    public function get($url)
    {
        return $this->read($url);
    }

    public function delete($url)
    {
        // ?
    }

    public function multiGet(array $urls)
    {
        $results = [];
        foreach ($urls as $url) {
            $results[$url] = $this->read($url);
        }
        return $results;
    }

    public function search($jql, $fields = null, array $expand = null, array $properties = null)
    {
        return $this->read($jql);
    }

    public function assertWritten($url, array $data = [])
    {
        $assert = isset($this->posts[$this->keyify($url)]) && ($this->posts[$this->keyify($url)] == $data);
        if (!$assert) {
            throw new \InvalidArgumentException(self::ERROR_CANNOT_WRITE_FIXTURE, $url);
        }
    }

    private function read($url)
    {
        $filename = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . $this->keyify($url);
        if (!is_readable($filename)) {
            throw new \InvalidArgumentException(sprintf(self::ERROR_NO_SUCH_FIXTURE, $url));
        }
        $data = unserialize(file_get_contents($filename));
        if (false === $data) {
            throw new \InvalidArgumentException(sprintf(self::ERROR_CANNOT_UNSERIALIZE_FIXTURE, $url));
        }
        return $data;
    }

    private function write($url, $data)
    {
        $this->posts[$this->keyify($url)] = $data;
    }

    private function keyify($key)
    {
        return strtr($key, ['/' => '--']);
    }
}
