<?php

declare(strict_types=1);

namespace Technodelight\Jira\Api\JiraRestApi;

use ICanBoogie\Storage\Storage;
use Technodelight\Jira\Configuration\ApplicationConfiguration\CurrentInstanceProvider;
use Technodelight\Jira\Configuration\ApplicationConfiguration\ProjectConfiguration;

class CachedHttpClient implements Client
{
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly Storage $storage,
        private readonly ProjectConfiguration $configuration,
        private readonly CurrentInstanceProvider $instanceProvider
    ) {}

    public function post($url, $data = [])
    {
        if (!str_contains('worklog/list', $url) && !str_contains('search', $url)) {
            $this->storage->clear();
        }
        return $this->httpClient->post($url, $data);
    }

    public function put($url, $data = [])
    {
        $this->storage->clear();
        return $this->httpClient->put($url, $data);
    }

    public function get($url)
    {
        $key = $this->keyify($url);
        $result = $this->storage->retrieve($key);
        if (!is_null($result)) {
            return $result;
        }
        $result = $this->httpClient->get($url);
        $this->storage->store($key, $result, $this->configuration->cacheTtl());
        return $result;
    }

    public function delete($url)
    {
        $this->storage->clear();
        return $this->httpClient->delete($url);
    }

    public function multiGet(array $urls): array
    {
        $cachedResults = [];
        $uncachedUrls = [];
        foreach ($urls as $url) {
            $result = $this->storage->retrieve($this->keyify($url));
            if (!is_null($result)) {
                $cachedResults[$url] = $result;
                continue;
            }

            $uncachedUrls[] = $url;
        }
        $results = [];
        foreach ($this->httpClient->multiGet($uncachedUrls) as $url => $result) {
            $key = $url;
            $this->storage->store($this->keyify($key), $result, $this->configuration->cacheTtl());
            $results[$key] = $result;
        }
        $mergedResults = [];
        foreach ($urls as $url) {
            $mergedResults[$url] = $cachedResults[$url] ?? $results[$url];
        }
        return $mergedResults;
    }

    public function search($jql, $startAt = null, $fields = null, array $expand = null, array $properties = null): array
    {
        $key = $this->keyify($jql, $startAt, is_array($fields) ? join(',', $fields) : $fields, serialize($expand), serialize($properties));
        $result = $this->storage->retrieve($key);
        if (!is_null($result)) {
            return $result;
        }
        $result = $this->httpClient->search($jql, $startAt, $fields, $expand, $properties);
        $this->storage->store($key, $result, $this->configuration->cacheTtl());
        return $result;
    }

    public function download($url, $filenameOrResource, callable $progressFunction = null)
    {
        $this->httpClient->download($url, $filenameOrResource, $progressFunction);
    }

    public function upload($url, $filename)
    {
        $this->httpClient->upload($url, $filename);
        $this->storage->clear();
    }

    private function keyify()
    {
        $components = func_get_args();
        array_unshift($components, $this->instanceProvider->currentInstance()->name());
        return md5(implode('', $components));
    }
}
