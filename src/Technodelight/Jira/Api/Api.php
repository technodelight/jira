<?php

namespace Technodelight\Jira\Api;

use Technodelight\Jira\Api\SearchQuery\Builder as SearchQueryBuilder;

class Api
{
    const FIELDS_ALL = '*all';
    const CURRENT_USER = 'currentUser()';
    const SPRINT_OPEN_SPRINTS = 'openSprints()';

    /**
     * @var Client
     */
    private $client;
    /**
     * @var SearchQueryBuilder
     */
    private $queryBuilder;

    private $defaultIssueTypeFilter = ['Defect', 'Bug', 'Technical Sub-Task'];

    private $myself;

    private $retrievedIssues = [];

    public function __construct(Client $client, SearchQueryBuilder $queryBuilder)
    {
        $this->client = $client;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return array
     */
    public function user()
    {
        if (!$this->myself) {
            $this->myself = $this->client->get('myself');
        }
        return $this->myself;
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
    public function worklog($issueKey, $timeSpent, $comment, $started, $adjustEstimate = 'auto', $newEstimate = null)
    {
        $params = ['adjustEstimate' => $adjustEstimate];
        if ($newEstimate) {
            $params['newEstimate'] = $newEstimate;
        }

        $jiraRecord = $this->client->post(
            sprintf('issue/%s/worklog', $issueKey) . '?' . http_build_query($params),
            [
                'comment' => $comment,
                'started' => $started,
                'timeSpent' => $timeSpent,
            ]
        );
        return Worklog::fromArray($jiraRecord, $issueKey);
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
    public function retrieveIssuesHavingWorklogsForUser($from, $to, $user = null)
    {
        $this->queryBuilder->resetActiveConditions();
        $this->queryBuilder->worklogAuthor($user ?: self::CURRENT_USER);
        $this->queryBuilder->worklogDate($from, $to);

        return $this->search($this->queryBuilder->assemble(), 'issueKey');
    }

    /**
     * @param string $projectCode
     * @param bool $all shows other's progress
     *
     * @return IssueCollection
     */
    public function inprogressIssues($projectCode, $all = false)
    {
        $this->queryBuilder->resetActiveConditions();
        $this->queryBuilder->project($projectCode);
        $this->queryBuilder->status('In Progress');
        if (!$all) {
            $this->queryBuilder->assignee(self::CURRENT_USER);
        }

        return $this->search($this->queryBuilder->assemble(), self::FIELDS_ALL);
    }

    public function filterIssuesByStatusAndType($projectCode, $status, array $issueTypes = [])
    {
        $this->queryBuilder->resetActiveConditions();
        $this->queryBuilder->project($projectCode);
        $this->queryBuilder->status($status);
        $this->queryBuilder->sprint(self::SPRINT_OPEN_SPRINTS);
        $this->queryBuilder->issueType($issueTypes ?: $this->defaultIssueTypeFilter);
        $this->queryBuilder->orderDesc('priority');

        return $this->search($this->queryBuilder->assemble());
    }

    /**
     * @param string $issueKey
     *
     * @return Issue
     */
    public function retrieveIssue($issueKey)
    {
        if (!isset($this->retrievedIssues[$issueKey])) {
            $this->retrievedIssues[$issueKey] = Issue::fromArray(
                $this->client->get(sprintf('issue/%s', $issueKey))
            );
        }

        return $this->retrievedIssues[$issueKey];
    }

    /**
     * @param  array  $issueKeys
     *
     * @return IssueCollection
     */
    public function retrieveIssues(array $issueKeys)
    {
        $this->queryBuilder->resetActiveConditions();
        $this->queryBuilder->issueKey($issueKeys);
        return $this->search($this->queryBuilder->assemble(), self::FIELDS_ALL);
    }

    /**
     * @param  string $issueKey
     * @param  array  $data
     *
     * @return array
     */
    public function updateIssue($issueKey, array $data)
    {
        return $this->client->put(sprintf('issue/%s', $issueKey), $data);
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
                [
                    'transition' => ['id' => $transitionId]
                ]
            );
    }

    /**
     * @param string $jql
     * @param string|null $fields
     * @param array|null $expand
     *
     * @return IssueCollection
     */
    public function search($jql, $fields = null, array $expand = null)
    {
        return IssueCollection::fromSearchArray(
            $this->client->search(
                $jql,
                $fields,
                !empty($expand) ? join(',', $expand) : null
            )
        );
    }
}
