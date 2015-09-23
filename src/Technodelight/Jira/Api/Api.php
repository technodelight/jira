<?php

namespace Technodelight\Jira\Api;

class Api
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return array
     */
    public function user()
    {
        return $this->client->get('myself');
    }

    /**
     * @param string $projectCode
     *
     * @return array
     */
    public function project($projectCode)
    {
        return $this->client->get('project/' . $projectCode);
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
        $result = $this->client->get(sprintf('issue/%s', $issueKey));
        return Issue::fromArray($result);
    }

    /**
     * @param string $issueKey
     *
     * @return array
     */
    public function retrievePossibleTransitionsForIssue($issueKey)
    {
        $result = $this->client->get(sprintf('issue/%s/transitions', $issueKey));
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
        return $this->client
            ->post(
                sprintf('issue/%s/transitions', $issueKey),
                ['transition' => ['id' => $transitionId]]
            );
    }

    /**
     * @param string $jql
     *
     * @return SearchResultList
     */
    private function search($jql)
    {
        return SearchResultList::fromArray($this->client->search($jql));
    }
}
