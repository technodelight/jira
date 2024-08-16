<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\IssueStats;

use Technodelight\Jira\Api\JiraRestApi\Client;

class StatCollectorApiClient implements Client
{
    public function __construct(
        private readonly Client $client,
        private readonly IssueStats $stats
    ) {}

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

    public function download($url, $filenameOrResource, callable $progressFunction = null)
    {
        return $this->client->download($url, $filenameOrResource, $progressFunction);
    }

    public function upload($url, $filename)
    {
        return $this->client->upload($url, $filename);
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function parseIssueKeys($string): array
    {
        return StringParser::parse($string);
    }

    private function view(array $issueKeys): void
    {
        foreach ($issueKeys as $issueKey) {
            $this->stats->view($issueKey);
        }
    }

    private function update(array $issueKeys): void
    {
        foreach ($issueKeys as $issueKey) {
            $this->stats->update($issueKey);
        }
    }
}
