<?php

namespace Technodelight\Jira\Api\JiraRestApi;

interface Client
{
    /**
     * @param string $url
     * @param array $data
     * @return mixed
     */
    public function post($url, $data = []);
    public function put($url, $data = []);
    public function get($url);
    public function delete($url);
    public function multiGet(array $urls);
    public function search($jql, $startAt = null, $fields = null, array $expand = null, array $properties = null);
    public function download($url, $filename, callable $progressFunction = null);
    public function upload($url, $filename);
}
