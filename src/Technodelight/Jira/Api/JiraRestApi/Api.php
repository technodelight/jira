<?php

namespace Technodelight\Jira\Api\JiraRestApi;

use Technodelight\Jira\Api\JiraRestApi\SearchQuery\Builder as SearchQueryBuilder;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Api\User;
use Technodelight\Jira\Api\Issue;
use Technodelight\Jira\Api\IssueCollection;
use Technodelight\Jira\Api\Worklog;
use Technodelight\Jira\Api\WorklogCollection;

class Api
{
    const FIELDS_ALL = '*all';
    const CURRENT_USER = 'currentUser()';
    const SPRINT_OPEN_SPRINTS = 'openSprints()';

    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return User
     */
    public function user()
    {
        return User::fromArray($this->client->get('myself'));
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
     * Return available projects
     * $recent returns the most recent x amount
     *
     * @param  int|null $recent
     *
     * @return array
     */
    public function projects($numberOfRecent = null)
    {
        return $this->client->get('project' . ($numberOfRecent ? '?recent=' . (int) $numberOfRecent : ''));
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
                'started' => DateHelper::dateTimeToJira($started),
                'timeSpent' => $timeSpent,
            ]
        );
        return Worklog::fromArray($this->normaliseDateFields($jiraRecord), $issueKey);
    }

    public function retrieveWorklogs(array $worklogIds)
    {
        $records = $this->client->post(
            'worklog/list?expand=properties',
            ['ids' => $worklogIds]
        );
        foreach ($records as $logRecord) {
            yield Worklog::fromArray($this->normaliseDateFields($logRecord), $logRecord['issueId']);
        }
    }

    public function updateWorklog(Worklog $worklog)
    {
        $jiraRecord = $this->client->put(
            sprintf('issue/%s/worklog/%d?adjustEstimate=auto', $worklog->issueKey(), $worklog->id()),
            [
                'comment' => $worklog->comment(),
                'started' => DateHelper::dateTimeToJira($worklog->date()),
                'timeSpentSeconds' => $worklog->timeSpentSeconds(),
            ]
        );
        return Worklog::fromArray($this->normaliseDateFields($jiraRecord), $jiraRecord['issueId']);
    }

    public function deleteWorklog(Worklog $worklog)
    {
        $this->client->delete(sprintf('issue/%s/worklog/%d?adjustEstimate=auto', $worklog->issueKey(), $worklog->id()));
    }

    /**
     * @param string $issueKey
     *
     * @return WorklogCollection
     */
    public function retrieveIssueWorklogs($issueKey, $limit = null)
    {
        try {
            $response = $this->client->get(sprintf('issue/%s/worklog' . ($limit ? '?maxResults='.$limit : ''), $issueKey));
            $results = WorklogCollection::createEmpty();
            if (!is_null($limit)) {
                $response['worklogs'] = array_slice($response['worklogs'], $limit * -1);
            }
            foreach ($response['worklogs'] as $jiraRecord) {
                $results->push(Worklog::fromArray($this->normaliseDateFields($jiraRecord), $issueKey));
            }

            return $results;
        } catch (\Exception $exception) {
            return WorklogCollection::createEmpty();
        }
    }

    /**
     * @param  IssueCollection $issues
     * @param  int|null $limit
     */
    private function fetchAndAssignWorklogsToIssues(IssueCollection $issues, $from = null, $to = null, $username = null, $limit = null)
    {
        $requests = [];
        foreach ($issues->keys() as $issueKey) {
            $requests[] = sprintf('issue/%s/worklog' . ($limit ? '?maxResults='.$limit : ''), $issueKey);
        }

        $responses = $this->client->multiGet($requests);
        foreach ($responses as $requestUrl => $response) {
            list ( ,$issueKey, ) = explode('/', $requestUrl, 3);
            $issue = $issues->find($issueKey);
            $worklogs = WorklogCollection::fromIssueArray($issue, $response['worklogs']);
            if ($from && $to) {
                $worklogs = $worklogs->filterByDate($from, $to);
            }
            if ($username) {
                $worklogs = $worklogs->filterByUser($username);
            }
            if ($limit) {
                $worklogs = $worklogs->filterByLimit($limit);
            }
            $issue->assignWorklogs($worklogs);
        }
    }

    /**
     * Find issues with matching worklogs for user
     *
     * @param string $from could be startOfWeek, startOfDay, Y-m-d
     * @param string $to could be startOfWeek, startOfDay, Y-m-d
     * @param string|null $username username or currentUser() by default. Must be a username given.
     * @param int|null $limit
     *
     * @return IssueCollection
     */
    public function findUserIssuesWithWorklogs($from, $to, $username = null, $limit = null)
    {
        $query = SearchQueryBuilder::factory()
            ->worklogDate($from, $to);
        if ($username) {
            $query->worklogAuthor($username);
        }

        $issues = $this->search($query->assemble(), 'issueKey');
        $this->fetchAndAssignWorklogsToIssues($issues, $from, $to, $username, $limit);

        return $issues;
    }

    /**
     * @param string $projectCode
     * @param bool $all shows other's progress
     *
     * @return IssueCollection
     */
    public function inprogressIssues($projectCode = null, $all = false)
    {
        $query = SearchQueryBuilder::factory()
            ->status('In Progress');
        if (!$all) {
            $query->assignee(self::CURRENT_USER);
        } else {
            $query->project($projectCode);
        }

        return $this->search($query->assemble(), self::FIELDS_ALL);
    }

    /**
     * @param string $issueKey
     *
     * @return Issue
     */
    public function retrieveIssue($issueKey)
    {
        return Issue::fromArray(
            $this->normaliseIssueArray($this->client->get(sprintf('issue/%s', $issueKey)))
        );
    }

    /**
     * @param  array  $issueKeys
     *
     * @return IssueCollection
     */
    public function retrieveIssues(array $issueKeys)
    {
        $query = SearchQueryBuilder::factory()
            ->issueKey($issueKeys);
        return $this->search($query->assemble(), self::FIELDS_ALL);
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
     * @param array|null $properties
     *
     * @return IssueCollection
     */
    public function search($jql, $fields = null, array $expand = null, array $properties = null)
    {
        $results = $this->client->search(
            $jql,
            $fields,
            $expand,
            $properties
        );
        foreach ($results['issues'] as $k => $issueArray) {
            $results['issues'][$k] = $this->normaliseIssueArray($issueArray);
        }
        return IssueCollection::fromSearchArray($results);
    }

    /**
     * Download URL to target filename
     *
     * @param string $url
     * @param string $filename
     */
    public function download($url, $filename)
    {
        $this->client->download($url, $filename);
    }

    /**
     * @param array $jiraIssue
     * @return array
     */
    private function normaliseIssueArray(array $jiraIssue)
    {
        $attachments = isset($jiraIssue['fields']['attachment']) ? $jiraIssue['fields']['attachment'] : [];
        foreach ($attachments as $k => $attachment) {
            $attachments[$k] = $this->normaliseDateFields($attachment);
        }
        $jiraIssue['fields']['attachment'] = $attachments;
        $parent = !empty($jiraIssue['parent']) ? $jiraIssue['parent'] : null;
        if ($parent) {
            $jiraIssue['fields']['parent'] = $this->normaliseDateFields($parent);
        }
        $comments = isset($jiraIssue['fields']['comment']) ? $jiraIssue['fields']['comment'] : [];
        if ($comments) {
            foreach ($comments['comments'] as $k => $comment) {
                $comments['comments'][$k] = $this->normaliseDateFields($comment);
            }
        }
        $jiraIssue['fields']['comment'] = $comments;
        $worklog = isset($jiraIssue['fields']['worklog']) ? $jiraIssue['fields']['worklog'] : [];
        if ($worklog) {
            foreach ($worklog['worklogs'] as $k => $comment) {
                $worklog['worklogs'][$k] = $this->normaliseDateFields($comment);
            }
        }
        $jiraIssue['fields']['worklog'] = $worklog;

        return $jiraIssue;
    }

    private function normaliseDateFields(array $jiraItem)
    {
        $fields = ['created', 'started', 'updated', 'createdAt', 'startedAt', 'updatedAt'];
        foreach ($fields as $field) {
            if (isset($jiraItem[$field])) {
                $jiraItem[$field] = $this->normaliseDate($jiraItem[$field]);
            }
        }
        return $jiraItem;
    }

    /**
     * @param string $jiraDate in idiot jira date format
     * @return string in normal date format
     */
    private function normaliseDate($jiraDate)
    {
        return DateHelper::dateTimeFromJira($jiraDate)->format('Y-m-d H:i:s');
    }
}
