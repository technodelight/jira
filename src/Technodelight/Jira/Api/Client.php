<?php

namespace Technodelight\Jira\Api;

use GuzzleHttp\Client as GuzzleClient;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Api\SearchResultList;
use Technodelight\Jira\Api\Issue;

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

    /**
     * @param Configuration $config
     */
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

    /**
     * @return array
     */
    public function user()
    {
        return $this->httpClient->get('myself')->json();
    }

    /**
     * @param string $projectCode
     *
     * @return array
     */
    public function project($projectCode)
    {
        return $this->httpClient->get('project/' . $projectCode)->json();
    }

    /**
     * @param string $projectCode
     *
     * @return SearchResultList
     */
    public function inprogressIssues($projectCode)
    {
        $query = sprintf('project = "%s" and assignee = currentUser() and status = "In Progress"', $projectCode);
        return $this->search($query);
    }

    /**
     * @param string $projectCode
     *
     * @return SearchResultList
     */
    public function todoIssues($projectCode)
    {
        $query = sprintf(
            'project = "%s" and status = "Open" and Sprint in openSprints() and issuetype in ("%s") ORDER BY priority DESC',
            $projectCode,
            implode('", "', ['Defect', 'Bug', 'Technical Sub-Task'])
        );
        return $this->search($query);
    }

    /**
     * @param string $issueKey
     *
     * @return Issue
     */
    public function retrieveIssue($issueKey)
    {
        $result = $this->httpClient->get(sprintf('issue/%s', $issueKey))->json();
        return Issue::fromArray($result);
    }

    /**
     * @param string $issueKey
     *
     * @return array
     */
    public function retrievePossibleTransitionsForIssue($issueKey)
    {
        $result = $this->httpClient->get(sprintf('issue/%s/transitions', $issueKey))->json();
        return isset($result['transitions']) ? $result['transitions'] : [];
    }

    /**
     * @param string $issueKey
     * @param int $transitionId returned by retrieveTransitions
     *
     * @return array
     */
    public function performIssueTransition($issueKey, $transitionId)
    {
        return $this->httpClient
            ->post(
                sprintf('issue/%s/transitions', $issueKey),
                [
                    'json' => [
                        'transition' => ['id' => $transitionId],
                    ]
                ]
            )
            ->json();
    }

    public function post($url, $data)
    {
        return $this->httpClient->post($url, ['json' => $data])->json();
    }

    public function get($url)
    {
        return $this->httpClient->get($url)->json();
    }

    /**
     * @param string $jql
     *
     * @return SearchResultList
     */
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
