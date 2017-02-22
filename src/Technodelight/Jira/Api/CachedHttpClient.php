<?php

namespace Technodelight\Jira\Api;

use ICanBoogie\Storage\Storage;
use Technodelight\Jira\Api\Api;
use Technodelight\Jira\Api\HttpClient;
use Technodelight\Jira\Configuration\ApplicationConfiguration;

class CachedHttpClient implements Client
{
    private $httpClient;
    private $storage;
    private $configuration;

    private $issueEndpoints = ['issue/%s', 'issue/%s/worklog', 'issue/%s/transitions'];

    public function __construct(
        HttpClient $httpClient,
        Storage $storage,
        ApplicationConfiguration $configuration
    )
    {
        $this->httpClient = $httpClient;
        $this->storage = $storage;
        $this->configuration = $configuration;
    }

    public function post($url, $data = [])
    {
        $key = $this->urlKey($url);
        $this->storage->eliminate($key);
        $this->purgeIssueEndpoints($url);
        $result = $this->httpClient->post($url, $data);
        return $result;
    }

    public function put($url, $data = [])
    {
        $key = $this->urlKey($url);
        $this->storage->eliminate($key);
        $this->purgeIssueEndpoints($url);
        $result = $this->httpClient->put($url, $data);
        return $result;
    }

    public function get($url)
    {
        $key = $this->urlKey($url);
        if ($this->storage->exists($key)) {
            return $this->storage->retrieve($key);
        }
        $result = $this->httpClient->get($url);
        $this->storage->store($key, $result, $this->configuration->cacheTtl());
        return $result;
    }

    public function delete($url)
    {
        $key = $this->urlKey($url);
        $this->storage->eliminate($key);
        $this->purgeIssueEndpoints($url);

        return $this->httpClient->delete($url);
    }

    public function multiGet(array $urls)
    {
        $cachedResults = [];
        $uncachedUrls = [];
        foreach ($urls as $idx => $url) {
            if ($this->storage->exists($this->urlKey($url))) {
                $cachedResults[$url] = $this->storage->retrieve($this->urlKey($url));
            } else {
                $uncachedUrls[] = $url;
            }
        }
        $results = [];
        foreach ($this->httpClient->multiGet($uncachedUrls) as $url => $result) {
            $key = $this->httpClient->effectiveUrlFromFull($url);
            $this->storage->store($this->urlKey($key), $result, $this->configuration->cacheTtl());
            $results[$key] = $result;
        }
        $mergedResults = [];
        foreach ($urls as $url) {
            $mergedResults[$url] = isset($cachedResults[$url]) ? $cachedResults[$url] : $results[$url];
        }
        return $mergedResults;
    }

    public function search($jql, $fields = null, $expand = null)
    {
        $key = $this->keyify($jql, (string) $fields, (string) $expand);
        if ($this->storage->exists($key)) {
            return $this->storage->retrieve($key);
        }
        $result = $this->httpClient->search($jql, $fields, $expand);
        $this->storage->store($key, $result, $this->configuration->cacheTtl());
        return $result;
    }

    private function purgeIssueEndpoints($url)
    {
        if ($issueKey = $this->issueKeyFromUrl($url)) {
            if (intval($issueKey) == $issueKey) {
                $issue = $this->httpClient->get('issue/' . $issueKey . '?fields=key');
                $issueKey = $issue['key'];
            }
            foreach ($this->issueEndpoints as $endpoint) {
                $this->storage->eliminate($this->urlKey(sprintf($endpoint, $issueKey)));
            }
        }
    }

    private function urlKey($url)
    {
        if ($issueKey = $this->issueKeyFromUrl($url)) {
            return parse_url($url, PHP_URL_PATH);
        }

        return $url;
    }

    private function issueKeyFromUrl($url)
    {
        $issueKey = null;
        if (preg_match('~issue/([^/]+)~', $url, $match)) {
            if (strpos($match[1], '/') !== false) {
                $issueKey = substr($match[1], strpos($match[1], '/'));
            } else {
                $issueKey = $match[1];
            }
        }

        return $issueKey;
    }

    private function keyify()
    {
        return md5(implode('', func_get_args()));
    }
}
