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
     * @param  array $issueKeys
     * @param  int|null $limit
     *
     * @return Worklog[]
     */
    public function retrieveIssuesWorklogs(array $issueKeys, $limit = null)
    {
        $requests = [];
        foreach ($issueKeys as $issueKey) {
            $requests[] = sprintf('issue/%s/worklog', $issueKey);
        }
        $responses = $this->client->multiGet($requests);
        $worklogs = [];
        foreach ($responses as $url => $response) {
            if (preg_match('~.+/issue/([^/]+)/worklog~', $url, $matches)) {
                $issueKey = $matches[1];
                if (!is_null($limit)) {
                    $response['worklogs'] = array_slice($response['worklogs'], $limit * -1);
                }
                foreach ($response['worklogs'] as $jiraRecord) {
                    $worklogs[] = Worklog::fromArray($jiraRecord, $issueKey);
                }
            }
        }

        return $worklogs;
    }

    /**
     * @param string $issueKey
     *
     * @return Worklog[]
     */
    public function retrieveIssueWorklogs($issueKey)
    {
        try {
            $response = $this->client->get(sprintf('issue/%s/worklog', $issueKey));

            $results = [];
            foreach ($response['worklogs'] as $jiraRecord) {
                $results[] = Worklog::fromArray($jiraRecord, $issueKey);
            }

            return $results;
        } catch (Exception $exception) {
            return [];
        }
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
     * @return IssueCollection
     */
    public function inprogressIssues($projectCode, $all = false)
    {
        $query = sprintf(
            'project = "%s"%s and status = "In Progress"',
            $projectCode,
            $all ? '' : ' and assignee = currentUser()'
        );
        return $this->search($query, '*all');
    }

    /**
     * @param string $projectCode
     * @param array|null $issueTypes
     *
     * @return IssueCollection
     */
    public function todoIssues($projectCode, array $issueTypes = [])
    {
        $issueTypeFilter = empty($issueTypes) ? ['Defect', 'Bug', 'Technical Sub-Task'] : $issueTypes;
        $query = sprintf(
            'project = "%s" and status = "Open" and Sprint in openSprints() and issuetype in ("%s") ORDER BY priority DESC',
            $projectCode,
            implode('", "', $issueTypeFilter)
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
     * @param string|null $fields
     *
     * @return IssueCollection
     */
    private function search($jql, $fields = null)
    {
        return IssueCollection::fromSearchArray($this->client->search($jql, $fields));
    }
}
