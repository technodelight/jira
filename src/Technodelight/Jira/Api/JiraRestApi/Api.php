<?php

namespace Technodelight\Jira\Api\JiraRestApi;

use DateTime;
use Technodelight\Jira\Api\JiraRestApi\SearchQuery\Builder as SearchQueryBuilder;
use Technodelight\Jira\Domain\Comment;
use Technodelight\Jira\Domain\Field;
use Technodelight\Jira\Domain\Issue\Meta;
use Technodelight\Jira\Domain\IssueLink;
use Technodelight\Jira\Domain\IssueLink\Type;
use Technodelight\Jira\Domain\Project;
use Technodelight\Jira\Domain\Status;
use Technodelight\Jira\Domain\Transition;
use Technodelight\Jira\Domain\UserPickerResult;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Domain\User;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\WorklogCollection;

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
     * Returns matching users for a query string in the format of
     *
     * ```
     * {
     * "users": [
     *      {
     *          "name": "fred",
     *          "key": "fred",
     *          "html": "fred@example.com",
     *          "displayName": "Fred Grumble",
     *          "avatarUrl": "http://www.example.com/jira/secure/useravatar?size=small&ownerId=fred"
     *      }
     * ],
     * "total": 25,
     * "header": "Showing 20 of 25 matching groups"
     * }
     * ```
     *
     * @param string $query	string A string used to search username, Name or e-mail address
     * @param int|null $maxResults the maximum number of users to return (defaults to 50). The maximum allowed value is 1000. If you specify a value that is higher than this number, your search results will be truncated.
     * @param bool|null $showAvatar boolean
     * @param string|null $exclude string
     * @return UserPickerResult[]
     */
    public function userPicker($query, $maxResults = null, $showAvatar = null, $exclude = null)
    {
        $response = $this->client->get(
            'user/picker' . $this->queryStringFromParams([
                'query' => $query,
                'maxResults' => $maxResults,
                'showAvatar' => $showAvatar,
                'exclude' => $exclude,
            ])
        );
        return array_map(
            function (array $user) {
                return UserPickerResult::fromArray($user);
            },
            $response['users']
        );
    }

    /**
     * @param string $projectKey
     *
     * @return Project
     */
    public function project($projectKey)
    {
        return Project::fromArray($this->client->get(sprintf('project/%s', $projectKey)));
    }

    /**
     * Return available projects
     * $recent returns the most recent x amount
     *
     * @param  int|null $recent
     *
     * @return Project[]
     */
    public function projects($numberOfRecent = null)
    {
        return array_map(
            function(array $project) {
                return Project::fromArray($project);
            },
            $this->client->get('project' . $this->queryStringFromParams(['recent' => $numberOfRecent ? (int) $numberOfRecent : null]))
        );
    }

    /**
     * Return available statuses for a project per issue type
     *
     * @param string $projectKey
     * @return array
     */
    public function projectStatuses($projectKey)
    {
        $response = $this->client->get(sprintf('project/%s/statuses', $projectKey));
        foreach (array_keys($response) as $k) {
            $response[$k]['statuses'] = array_map(
                function (array $status) {
                    return Status::fromArray($status);
                },
                $response[$k]['statuses']
            );
        }
        return $response;
    }

    /**
     * Return all available statuses across the current instance
     *
     * @return Status[]
     */
    public function status()
    {
        return array_map(
            function (array $status) {
                return Status::fromArray($status);
            },
            $this->client->get('status')
        );
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
     * @param int $timeSpentSeconds
     * @param string $comment
     * @param string $adjustEstimate
     * @param string $newEstimate if adjustEstimate is 'new' this arg should be provided
     *
     */
    public function worklog($issueKey, $timeSpentSeconds, $comment, $started, $adjustEstimate = 'auto', $newEstimate = null)
    {
        $params = ['adjustEstimate' => $adjustEstimate];
        if ($newEstimate) {
            $params['newEstimate'] = $newEstimate;
        }

        $jiraRecord = $this->client->post(
            sprintf('issue/%s/worklog', $issueKey) . $this->queryStringFromParams($params),
            [
                'comment' => $comment,
                'started' => DateHelper::dateTimeToJira($started),
                'timeSpentSeconds' => $timeSpentSeconds,
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
     * @param \Technodelight\Jira\Domain\IssueCollection $issues
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     * @param string|null $username
     * @param int|null $limit
     */
    private function fetchAndAssignWorklogsToIssues(IssueCollection $issues, DateTime $from = null, DateTime $to = null, $username = null, $limit = null)
    {
        $requests = [];
        foreach ($issues->keys() as $issueKey) {
            $requests[] = sprintf('issue/%s/worklog' . ($limit ? '?maxResults='.$limit : ''), $issueKey);
        }

        $responses = $this->client->multiGet($requests);
        foreach ($responses as $requestUrl => $response) {
            list ( ,$issueKey, ) = explode('/', $requestUrl, 3);
            $issue = $issues->find($issueKey);
            foreach ($response['worklogs'] as $k => $log) {
                $response['worklogs'][$k] = $this->normaliseDateFields($log);
            }
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
     * @param DateTime $from
     * @param DateTime $to
     * @param string|null $username username or currentUser() by default. Must be a username given.
     * @param int|null $limit
     *
     * @return IssueCollection
     */
    public function findUserIssuesWithWorklogs(DateTime $from, DateTime $to, $username = null, $limit = null)
    {
        $query = SearchQueryBuilder::factory()
            ->worklogDate($from->format('Y-m-d'), $to->format('Y-m-d'));
        if ($username) {
            $query->worklogAuthor($username);
        }

        $issues = $this->search($query->assemble(), 'issueKey');
        $this->fetchAndAssignWorklogsToIssues($issues, $from, $to, $username, $limit);

        return $issues;
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
     * Edits the issue from a JSON representation.
     *
     * The fields available for update can be determined using the /rest/api/2/issue/{issueIdOrKey}/editmeta resource.
     * If a field is hidden from the Edit screen then it will not be returned by the editmeta resource. A field
     * validation error will occur if such field is submitted in an edit request. However connect add-on with admin
     * scope may override a screen security configuration.
     * If an issue cannot be edited in Jira because of its workflow status (for example the issue is closed), then
     * you will not be able to edit it with this resource.
     * Field to be updated should appear either in fields or update request’s body parameter, but not in both.
     * To update a single sub-field of a complex field (e.g. timetracking) please use the update parameter of the edit
     * operation. Using a “field_id”: field_value construction in the fields parameter is a shortcut of “set” operation
     * in the update parameter.
     *
     * @param  string $issueKey
     * @param  array  $data
     *
     * @see Api::issueEditMeta()
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-issue-issueIdOrKey-put
     * @return array
     */
    public function updateIssue($issueKey, array $data)
    {
        return $this->client->put(sprintf('issue/%s', $issueKey), $data);
    }

    /**
     * Update issue assignee
     *
     * @param string $issueKey
     * @param string $usernameKey
     * @return mixed
     */
    public function assignIssue($issueKey, $usernameKey)
    {
        return $this->client->put(sprintf('issue/%s/assignee', $issueKey), ['name' => $usernameKey]);
    }

    /**
     * Returns the keys of all properties for the issue identified by the key or by the id.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-issue-issueIdOrKey-properties-get
     * @param string $issueKey
     * @return array
     */
    public function issueProperties($issueKey)
    {
        return $this->client->get(sprintf('issue/%s/properties', $issueKey));
    }

    /**
     * Returns the metadata for editing an issue.
     * The fields returned by editmeta resource are the ones shown on the issue’s Edit screen. Fields hidden from the
     * screen will not be returned unless `overrideScreenSecurity` parameter is set to true.
     * If an issue cannot be edited in Jira because of its workflow status (for example the issue is closed), then no
     * fields will be returned, unless `overrideEditableFlag` is set to true.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-issue-issueIdOrKey-editmeta-get
     * @param string $issueKey
     * @param bool|null $screenSecurity overrideScreenSecurity
     * @param bool|null $editableFlag overrideEditableFlag
     * @return Meta
     */
    public function issueEditMeta($issueKey, $screenSecurity = null, $editableFlag = null)
    {
        $result = $this->client->get(
            sprintf('issue/%s/editmeta', $issueKey) . $this->queryStringFromParams([
                'overrideScreenSecurity' => $screenSecurity,
                'overrideEditableFlag' => $editableFlag,
            ])
        );
        return Meta::fromArrayAndIssueKey($result['fields'], $issueKey);
    }

    /**
     * @param string $issueKey
     *
     * @return Transition[]
     */
    public function retrievePossibleTransitionsForIssue($issueKey)
    {
        $result = $this->client->get(sprintf('issue/%s/transitions', $issueKey));
        if (isset($result['transitions'])) {
            return array_map(
                function(array $transition) {
                    return Transition::fromArray($transition);
                },
                $result['transitions']
            );
        }
        return [];
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
        try {
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
        } catch (\Exception $e) {
            throw new \BadMethodCallException(
                $e->getMessage() . PHP_EOL
                . 'See advanced search help at https://confluence.atlassian.com/jiracorecloud/advanced-searching-765593707.html' . PHP_EOL
                . 'Query was "' . $jql . '"',
                $e->getCode(),
                $e
            );
        }
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
     * @param string $issueKey
     * @param string $comment
     * @return \Technodelight\Jira\Domain\Comment
     */
    public function addComment($issueKey, $comment)
    {
        $response = $this->client->post(
            sprintf('issue/%s/comment', $issueKey),
            [
                'body' => $comment
            ]
        );
        return Comment::fromArray($this->normaliseDateFields($response));
    }

    /**
     * @param string $issueKey
     * @param string $commentId
     * @param string $comment
     * @return \Technodelight\Jira\Domain\Comment
     */
    public function updateComment($issueKey, $commentId, $comment)
    {
        $response = $this->client->put(
            sprintf('issue/%s/comment/%s', $issueKey, $commentId),
            [
                'body' => $comment
            ]
        );
        return Comment::fromArray($this->normaliseDateFields($response));
    }

    /**
     * @param string $issueKey
     * @param string $commentId
     * @return bool
     */
    public function deleteComment($issueKey, $commentId)
    {
        $this->client->delete(sprintf('issue/%s/comment/%s', $issueKey, $commentId));
        return true;
    }

    /**
     * @param string $query	Query used to filter issue search results.
     * @param string $currentJql JQL defining search context. Only issues matching this JQL query are included in the results.
     * @param string $currentIssueKey Key of the issue defining search context. The issue defining a context is excluded from the search results.
     * @param string $currentProjectId ID of a project defining search context. Only issues belonging to a given project are suggested.
     * @param bool $showSubTasks Set to false to exclude subtasks from the suggestions list.
     * @param bool $showSubTaskParent Set to false to exclude parent issue from the suggestions list if search is performed in the context of a sub-task.
     * @return IssueCollection
     * @throws \ErrorException if sections is missing from picker response
     */
    public function issuePicker(
        $query = null,
        $currentJql = null,
        $currentIssueKey = null,
        $currentProjectId = null,
        $showSubTasks = null,
        $showSubTaskParent = null
    )
    {
        $response = $this->client->get(
            'issue/picker' . $this->queryStringFromParams([
                'query' => $query,
                'currentJQL' => $currentJql,
                'currentIssueKey' => $currentIssueKey,
                'currentProjectId' => $currentProjectId,
                'showSubTasks' => $showSubTasks,
                'showSubTaskParent' => $showSubTaskParent
            ])
        );
        if (empty($response['sections'])) {
            throw new \ErrorException(
                '"sections" is missing from response'
            );
        }
        $issueKeys = [];
        foreach ($response['sections'] as $section) {
            foreach($section['issues'] as $pickedIssue) {
                $issueKeys[] = $pickedIssue['key'];
            }
        }

        return $this->retrieveIssues($issueKeys);
    }

    /**
     * Return all available issue fields
     *
     * @return Field[]
     */
    public function fields()
    {
        return array_map(
            function (array $field) {
                return Field::fromArray($field);
            },
            $this->client->get('field')
        );
    }

    /**
     * Creates an issue link between two issues. The user requires the link issue permission for the issue which will
     * be linked to another issue. The specified link type in the request is used to create the link and will create
     * a link from the first issue to the second issue using the outward description. It also create a link from the
     * second issue to the first issue using the inward description of the issue link type. It will add the supplied
     * comment to the first issue. The comment can have a restriction who can view it. If group is specified, only
     * users of this group can view this comment, if roleLevel is specified only users who have the specified role
     * can view this comment. The user who creates the issue link needs to belong to the specified group or have the
     * specified role.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-issueLink-post
     * @param string $inwardIssueKey
     * @param string $outwardIssueKey
     * @param string $linkName
     * @param string $comment
     * @return IssueLink
     */
    public function linkIssue($inwardIssueKey, $outwardIssueKey, $linkName, $comment = '')
    {
        $data = [
            'type' => ['name' => (string) $linkName],
            'inwardIssue' => ['key' => (string) $inwardIssueKey],
            'outwardIssue' => ['key' => (string) $outwardIssueKey],
            'comment' => !empty($comment) ? ['body' => (string) $comment] : false,
        ];
        $this->client->post('issueLink', array_filter($data));

        return IssueLink::fromArray($data);
    }

    /**
     * Returns an issue link with the specified id.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-issueLink-linkId-get
     * @param string $linkId
     * @return \Technodelight\Jira\Domain\IssueLink
     */
    public function retrieveIssueLink($linkId)
    {
        return IssueLink::fromArray($this->client->get(sprintf('issueLink/%s', $linkId)));
    }

    /**
     * Deletes an issue link with the specified id. To be able to delete an issue link
     * you must be able to view both issues and must have the link issue permission for
     * at least one of the issues.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-issueLink-linkId-delete
     *
     * @param string $linkId
     * @return bool
     */
    public function removeIssueLink($linkId)
    {
        $this->client->delete(sprintf('issueLink/%s', $linkId));
        return true;
    }

    /**
     * Returns a list of available issue link types, if issue linking is enabled.
     * Each issue link type has an id, a name and a label for the outward and inward link relationship.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-issueLinkType-get
     * @return Type[]
     */
    public function linkTypes()
    {
        return array_map(
            function(array $linkType) { return Type::fromArray($linkType); },
            $this->client->get('issueLinkType')['issueLinkTypes']
        );
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
        $jiraIssue['fields'] = $this->normaliseDateFields($jiraIssue['fields']);

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
     * @return string in normal Y-m-d H:i:s date format
     */
    private function normaliseDate($jiraDate)
    {
        return DateHelper::dateTimeFromJira($jiraDate)->format(DateHelper::FORMAT_FROM_JIRA);
    }

    private function queryStringFromParams(array $query)
    {
        $params = http_build_query(array_filter($query, function($value) { return !is_null($value); }));
        return $params ? '?' . $params : '';
    }
}
