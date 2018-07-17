<?php

namespace Technodelight\Jira\Console\IssueStats;

use Technodelight\Jira\Api\JiraRestApi\Client;

class StatCollectorApiClient implements Client
{
    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\Client
     */
    private $client;
    /**
     * @var \Technodelight\Jira\Console\IssueStats\IssueStats
     */
    private $stats;

    public function __construct(Client $client, IssueStats $stats)
    {
        $this->client = $client;
        $this->stats = $stats;
    }

    public function post($url, $data = [])
    {
        $result = $this->client->post($url, $data);
        $this->update($this->parseIssueKeys($url));
        return $result;
    }

    public function put($url, $data = [])
    {
        $result = $this->client->put($url, $data);
        $this->update($this->parseIssueKeys($url));
        return $result;
    }

    public function get($url)
    {
        $result = $this->client->get($url);
        $this->view($this->parseIssueKeys($url));
        return $result;
    }

    public function delete($url)
    {
        $result = $this->client->delete($url);
        $this->update($this->parseIssueKeys($url));
        return $result;
    }

    public function multiGet(array $urls)
    {
        $result = $this->client->multiGet($urls);
        foreach ($urls as $url) {
            $this->view($this->parseIssueKeys($url));
        }
        return $result;
    }

    public function search($jql, $startAt = null, $fields = null, array $expand = null, array $properties = null)
    {
        $result = $this->client->search($jql, $startAt, $fields, $expand, $properties);
        $this->view($this->parseIssueKeys($jql));
        return $result;
    }

    public function download($url, $filename)
    {
        return $this->client->download($url, $filename);
    }

    private function parseIssueKeys($string)
    {
        return StringParser::parse($string);
    }

    private function view(array $issueKeys)
    {
        foreach ($issueKeys as $issueKey) {
            $this->stats->view($issueKey);
        }
    }

    private function update(array $issueKeys)
    {
        foreach ($issueKeys as $issueKey) {
            $this->stats->update($issueKey);
        }
    }
}
