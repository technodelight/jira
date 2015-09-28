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
     * Log work against ticket
     *
     * $adjustEstimate options:
     * "new" - sets the estimate to a specific value
     * "leave"- leaves the estimate as is
     * "manual" - specify a specific amount to increase remaining estimate by
     * "auto"- Default option. Will automatically adjust the value based on the new timeSpent specified on the worklog
     *
     * @param string $issueKey
     * @param string $timeSpent like '2d'
     * @param string $comment
     * @param string $adjustEstimate
     * @param string $newEstimate if adjustEstimate is 'new' this arg should be provided
     *
     */
    public function worklog($issueKey, $timeSpent, $comment, $adjustEstimate = 'auto', $newEstimate = null)
    {
        $params = ['adjustEstimate' => $adjustEstimate];
        if ($newEstimate) {
            $params['newEstimate'] = $newEstimate;
        }

        return $this->client->post(
            sprintf('issue/%s/worklog', $issueKey) . '?' . http_build_query($params),
            [
                'comment' => $comment,
                'started' => date('Y-m-d\TH:i:s.000O'),
                'timeSpent' => $timeSpent,
            ]
        );
    }

    /**
     * @param string $issueKey
     *
     * @return array
     */
    public function retrieveIssueWorklogs($issueKey)
    {
        return $this->client->get(sprintf('issue/%s/worklog', $issueKey));
    }

    /**
     * Retrieve issues having worklog after given date on
     *
     * @param string $from could be startOfWeek or startOfDay
     * @param string $to could be startOfWeek or startOfDay
     * @param string|null $user username or currentUser() by default
     *
     * @return array
     */
    public function retrieveIssuesHavingWorklogsForUser($from = 'startOfWeek()', $to = null, $user = null)
    {
        return $this->search(
            sprintf(
                'worklogDate >= %s %sAND worklogAuthor = %s',
                $from,
                $to ? 'AND worklogDate <= ' . $to . ' ' : '',
                $user ?: 'currentUser()'
            )
        );
    }

    /**
     * @param string $projectCode
     * @param bool $all shows other's progress
     *
     * @return SearchResultList
     */
    public function inprogressIssues($projectCode, $all = false)
    {
        $query = sprintf(
            'project = "%s"%s and status = "In Progress"',
            $projectCode,
            $all ? '' : ' and assignee = currentUser()'
        );
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
