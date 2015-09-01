<?php

namespace Technodelight\Jira;

class Client
{
    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $config)
    {
        $this->httpClient = new GuzzleClient(
            [
                'base_url' => $this->apiUrl($config->domain()),
                'defaults' => [
                    'auth' => [$config->username(), $config->password()]
                ]
            ]
        );
    }

    public function user()
    {
        return $this->httpClient->get('myself')->json();
    }

    public function project($code)
    {
        return $this->httpClient->get('project/' . $code)->json();
    }

    public function inprogressIssues($projectCode)
    {
        $query = sprintf('project = "%s" and assignee = currentUser() and status = "In Progress"', $projectCode);
        return $this->search($query);
    }

    public function todoIssues($projectCode)
    {
        $query = sprintf(
            'project = "%s" and status = "Open" and Sprint in openSprints() and issuetype in ("%s") ORDER BY priority DESC',
            $projectCode,
            implode('", "', ['Defect', 'Bug', 'Technical Sub-Task'])
        );
        return $this->search($query);
    }

    private function search($jql)
    {
        $response = $this->httpClient->get('search', ['query' => ['jql' => $jql]]);
        return SearchResultList::fromArray($response->json());
    }

    private function apiUrl($projectDomain)
    {
        return sprintf(
            'https://%s/rest/api/2/',
            $projectDomain
        );
    }
}
