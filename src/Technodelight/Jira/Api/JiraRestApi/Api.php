<?php

namespace Technodelight\Jira\Api\JiraRestApi;

use BadMethodCallException;
use DateTime;
use Sirprize\Queried\QueryException;
use Technodelight\Jira\Api\JiraRestApi\SearchQuery\Builder as SearchQueryBuilder;
use Technodelight\Jira\Domain\Comment\CommentId;
use Technodelight\Jira\Domain\Filter\FilterId;
use Technodelight\Jira\Domain\Issue\IssueId;
use Technodelight\Jira\Domain\IssueLink\IssueLinkId;
use Technodelight\Jira\Domain\Worklog\WorklogId;
use Technodelight\Jira\Domain\Comment;
use Technodelight\Jira\Domain\Field;
use Technodelight\Jira\Domain\Filter;
use Technodelight\Jira\Domain\Issue\Changelog;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\Issue\Meta;
use Technodelight\Jira\Domain\IssueLink;
use Technodelight\Jira\Domain\IssueLink\Type;
use Technodelight\Jira\Domain\Priority;
use Technodelight\Jira\Domain\Project;
use Technodelight\Jira\Domain\Project\ProjectKey;
use Technodelight\Jira\Domain\Status;
use Technodelight\Jira\Domain\Transition;
use Technodelight\Jira\Domain\UserPickerResult;
use Technodelight\Jira\Domain\User;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\WorklogCollection;

class Api
{
    public const FIELDS_ALL = '*all';
    private const SEARCH_HELP_LINK = 'https://support.atlassian.com/'
        . 'jira-work-management/docs/use-advanced-search-with-jira-query-language-jql/';

    public function __construct(private readonly Client $client)
    {
    }

    /**
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-user-get
     * @return User
     */
    public function user($accountId = null)
    {
        if (null === $accountId) {
            return User::fromArray($this->client->get('myself'));
        }

        return User::fromArray(
            $this->client->get(
                'user' . $this->queryStringFromParams([
                    'accountId' => $accountId,
                ])
            )
        );
    }

    /**
     * Returns a paginated list of the users specified by one or more account IDs.
     *
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-users/#api-rest-api-3-user-bulk-get
     * @param array $accountIds
     * @return User[]
     */
    public function users(array $accountIds)
    {
        if (empty($accountIds)) {
            return [];
        }
        $accountIdString = '';
        foreach (array_unique($accountIds) as $accountId) {
            $accountIdString.= (strlen($accountIdString) ? '&' : '') . 'accountId=' . $accountId;
        }

        return array_filter(array_map(
            static function (?array $user): ?User {
                return $user ? User::fromArray($user) : null;
            },
            $this->client->get(
                'user/bulk?' . $accountIdString
            )['values'] ?? []
        ));
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
     * the maximum number of users to return (defaults to 50). The maximum allowed value is 1000.
     * If you specify a value that is higher than this number, your search results will be truncated.
     * @param string $query string A string used to search username, Name or e-mail address
     * @param int|null $maxResults
     * @param bool|null $showAvatar boolean
     * @param string|null $exclude string
     * @return UserPickerResult[]
     */
    public function userPicker(
        string $query, ?int $maxResults = null, ?bool $showAvatar = null, ?string $exclude = null
    ): array {
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
     * Return a list of assignable users for a given query and/or issue
     *
     * @param string $query
     * @param IssueKey|null $issueKey
     * @param int $maxResults
     * @return array
     */
    public function assignablePicker(
        string $query,
        ?IssueKey $issueKey = null,
        int $maxResults = 20
    ): array {
        $users = $this->client->get(
            'user/assignable/search' . $this->queryStringFromParams([
                'query' => $query,
                'issueKey' => (string)$issueKey,
                'maxResults' => $maxResults
            ])
        );
        return array_map(static function(array $user) { return User::fromArray($user); }, $users);
    }

    /**
     * Retrieve project
     *
     * @param ProjectKey $projectKey
     *
     * @return Project
     */
    public function project(ProjectKey $projectKey): Project
    {
        return Project::fromArray($this->client->get(sprintf('project/%s', $projectKey)));
    }

    /**
     * Return available projects
     * $recent returns the most recent x amount
     *
     * @param  int|null $numberOfRecent
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
     * @param ProjectKey $projectKey
     * @return array
     */
    public function projectStatuses(ProjectKey $projectKey)
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
     * @param Worklog $worklog
     * @return Worklog
     */
    public function createWorklog(Worklog $worklog)
    {
        $jiraRecord = $this->client->post(
            sprintf('issue/%s/worklog', $worklog->issueIdentifier()) . $this->queryStringFromParams(['adjustEstimate' => 'auto']),
            [
                'comment' => $worklog->comment(),
                'started' => DateHelper::dateTimeToJira($worklog->date()),
                'timeSpentSeconds' => $worklog->timeSpentSeconds(),
            ]
        );
        return Worklog::fromArray($this->normaliseDateFields($jiraRecord), $worklog->issueIdentifier());
    }

    /**
     * @param WorklogId[] $worklogIds
     * @return \Generator
     */
    public function retrieveWorklogs(array $worklogIds)
    {
        $records = $this->client->post(
            'worklog/list?expand=properties,issueKey',
            [
                'ids' => array_map(function (WorklogId $worklogId) {
                    return (string) $worklogId;
                }, $worklogIds)
            ]
        );

        foreach ($records as $logRecord) {
            yield Worklog::fromArray($this->normaliseDateFields($logRecord), IssueId::fromNumeric($logRecord['issueId']));
        }
    }

    public function updateWorklog(Worklog $worklog): Worklog
    {
        $jiraRecord = $this->client->put(
            sprintf('issue/%s/worklog/%d?adjustEstimate=auto', $worklog->issueIdentifier(), (string) $worklog->id()),
            [
                'comment' => $worklog->comment(),
                'started' => DateHelper::dateTimeToJira($worklog->date()),
                'timeSpentSeconds' => $worklog->timeSpentSeconds(),
            ]
        );

        return Worklog::fromArray($this->normaliseDateFields($jiraRecord), IssueId::fromNumeric($jiraRecord['issueId']));
    }

    public function deleteWorklog(Worklog $worklog): void
    {
        $this->client->delete(sprintf('issue/%s/worklog/%d?adjustEstimate=auto', $worklog->issueKey() ?: $worklog->issueId(), (string) $worklog->id()));
    }

    /**
     * @param IssueKey $issueKey
     *
     * @return WorklogCollection
     */
    public function retrieveIssueWorklogs(IssueKey $issueKey, $limit = null)
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
     * @param IssueCollection $issues
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param string|null $username
     * @param int|null $limit
     */
    private function fetchAndAssignWorklogsToIssues(IssueCollection $issues, DateTime $from = null, DateTime $to = null, User $user = null, $limit = null)
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
            if ($user) {
                $worklogs = $worklogs->filterByUser($user);
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
     * @param User|null $user
     * @param int|null $limit
     *
     * @return IssueCollection
     */
    public function findUserIssuesWithWorklogs(DateTime $from, DateTime $to, User $user = null, $limit = null)
    {
        $query = SearchQueryBuilder::factory()
            ->worklogDate($from->format('Y-m-d'), $to->format('Y-m-d'));
        if ($user) {
            $query->worklogAuthor($user);
        }

        $issues = $this->search($query->assemble(), null, 'issueKey');
        $this->fetchAndAssignWorklogsToIssues($issues, $from, $to, $user, $limit);

        return $issues;
    }

    /**
     * @param IssueKey|IssueId $issueKey
     *
     * @return Issue
     */
    public function retrieveIssue(IssueKey|IssueId $issueKey): Issue
    {
        return Issue::fromArray(
            $this->normaliseIssueArray($this->client->get(sprintf('issue/%s', $issueKey)))
        );
    }

    /**
     * @param IssueKey[]|string[] $issueKeys
     * @return IssueCollection
     * @throws QueryException
     */
    public function retrieveIssues(array $issueKeys): IssueCollection
    {
        $query = SearchQueryBuilder::factory()
            ->issueKey($issueKeys);
        $result = IssueCollection::createEmpty();
        $startAt = 0;
        do {
            $issues = $this->search($query->assemble(), $startAt, self::FIELDS_ALL);
            $result->merge($issues);
            if (!$issues->isLast()) {
                $startAt+= 50;
            }
        } while (!$issues->isLast());

        return $result;
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
     *
     * @param IssueKey $issueKey
     * @param array $data
     * @param array $params
     * @return array
     */
    public function updateIssue(IssueKey $issueKey, array $data, array $params = [])
    {
        return $this->client->put(sprintf('issue/%s', $issueKey) . $this->queryStringFromParams($params), $data);
    }

    /**
     * Update issue assignee
     *
     * Assigns an issue to a user. Use this operation when the calling user does not have the Edit Issues permission
     * but has the Assign issue permission for the project that the issue is in.
     *
     * Note that:
     * - Only the name property needs to be set in the request object.
     * - If name in the request object is set to "-1", then the issue is assigned to the default assignee
     *   for the project.
     * - If name in the request object is set to null, then the issue is set to unassigned.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-api-3-issue-issueIdOrKey-assignee-put
     *
     * @param IssueKey $issueKey
     * @param string|int|null $usernameKey
     * @return mixed
     */
    public function assignIssue(IssueKey $issueKey, mixed $usernameKey): ?array
    {
        if (is_string($usernameKey)) {
            $users = $this->client->get(
                sprintf(
                    'user/assignable/search?query=%s&issueKey=%s',
                    $usernameKey,
                    $issueKey
                )
            );
            // change to default user if accountId cannot be resolved
            $usernameKey = $users[0]['accountId'] ?? -1;
        }

        return $this->client->put(sprintf('issue/%s/assignee', $issueKey), ['accountId' => $usernameKey]);
    }

    /**
     * Returns the keys of all properties for the issue identified by the key or by the id.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-issue-issueIdOrKey-properties-get
     * @param IssueKey $issueKey
     * @return array
     */
    public function issueProperties(IssueKey $issueKey)
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
     * @param IssueKey $issueKey
     * @param bool|null $screenSecurity overrideScreenSecurity
     * @param bool|null $editableFlag overrideEditableFlag
     * @return Meta
     */
    public function issueEditMeta(IssueKey $issueKey, ?bool $screenSecurity = null, ?bool $editableFlag = null): Meta
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
     * Returns a paginated list of all updates of an issue, sorted by date, starting from the oldest.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-issue-issueIdOrKey-changelog-get
     * @param IssueKey $issueKey
     * @param null|int $startAt
     * @param null|int $maxResults
     * @return Changelog[]
     */
    public function issueChangelogs(IssueKey $issueKey, ?int $startAt = null, ?int $maxResults = null): array
    {
        $result = $this->client->get(
            sprintf('issue/%s/changelog', $issueKey) . $this->queryStringFromParams([
                'startAt' => $startAt,
                'maxResults' => $maxResults,
            ])
        );
        $self = $this;
        return array_map(function(array $changelog) use ($self, $issueKey) {
            $this->replaceAccountIds($changelog);
            return Changelog::fromArray($self->normaliseDateFields($changelog), $issueKey);
        }, $result['values']);
    }

    /**
     * Performs an autocompletetion with issue meta autocomplete
     *
     * @param string $autocompleteUrl
     * @param string $query
     * @return mixed
     */
    public function autocompleteUrl($autocompleteUrl, $query)
    {
        return $this->client->get($autocompleteUrl . $query);
    }

    /**
     * Returns either all transitions or a transition that can be performed by the user on an issue, based on the issue's status.
     * Note, if a request is made for a transition that does not exist or cannot be performed on the issue, given its status, the response will return any empty transitions list.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/?utm_source=%2Fcloud%2Fjira%2Fplatform%2Frest%2F&utm_medium=302#api-api-3-issue-issueIdOrKey-transitions-get
     * @param IssueKey $issueKey
     * @return Transition[]
     */
    public function retrievePossibleTransitionsForIssue(IssueKey $issueKey)
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
     * Performs an issue transition and, if the transition has a screen, updates the fields from the transition screen. Optionally, issue properties can be set.
     * To update the fields on the transition screen, specify the fields in the fields or update parameters in the request body. Get details about the fields by calling fields by Get transition and using the transitions.fields expand.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/?utm_source=%2Fcloud%2Fjira%2Fplatform%2Frest%2F&utm_medium=302#api-api-3-issue-issueIdOrKey-transitions-post
     * @param IssueKey $issueKey
     * @param int $transitionId returned by retrieveTransitions
     * @return array
     */
    public function performIssueTransition(IssueKey $issueKey, Transition $transition)
    {
        return $this->client
            ->post(
                sprintf('issue/%s/transitions', $issueKey),
                [
                    'transition' => ['id' => $transition->id()]
                ]
            );
    }

    /**
     * Search for issues using jql
     *
     * The fields param (which can be specified multiple times) gives a comma-separated list of fields to include in the response.
     * This can be used to retrieve a subset of fields. A particular field can be excluded by prefixing it with a minus.
     * By default, only navigable (*navigable) fields are returned in this search resource. Note: the default is different
     * in the get-issue resource – the default there all fields (*all).
     *
     * Properties: The properties param is similar to fields and specifies a comma-separated list of issue properties to include.
     * Unlike fields, properties are not included by default. It is also not allowed to request all properties. The number of
     * different properties that may be requested in one query is limited to 5.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-search-post
     * @param string $jql a JQL query string
     * @param int|null $startAt
     * @param array|string|null $fields the list of fields to return for each issue. By default, all navigable fields are returned.
     * @param array|null $expand A list of the parameters to expand.
     * @param array|null $properties the list of properties to return for each issue. By default no properties are returned.
     *
     * @return IssueCollection
     */
    public function search($jql, $startAt = null, $fields = null, array $expand = null, array $properties = [])
    {
        try {
            // prepare JQL to replace usernames with account IDs
            $replacedJqlResult = $this->client->post('jql/pdcleaner', ['queryStrings' => [$jql]]);
            $replacedJql = $replacedJqlResult['queryStrings'][0] ?? $jql;

            $results = $this->client->search(
                $replacedJql,
                $startAt,
                $fields,
                $expand,
                $properties
            );
            foreach ($results['issues'] as $k => $issueArray) {
                $results['issues'][$k] = $this->normaliseIssueArray($issueArray);
            }

            return IssueCollection::fromSearchArray($results);
        } catch (ClientException $e) {
            throw new BadMethodCallException(
                $e->getMessage() . PHP_EOL
                . sprintf('See advanced search help at %s', self::SEARCH_HELP_LINK) . PHP_EOL
                . 'Query was: ' . PHP_EOL . $jql
            );
        }
    }

    /**
     * Returns an issue priority.
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-api-3-priority-id-get
     * @param int $priorityId
     * @return Priority
     */
    public function priority($priorityId)
    {
        return Priority::fromArray($this->client->get(sprintf('priority/%d', $priorityId)));
    }

    /**
     * Download URL to target filename
     *
     * @param string $url
     * @param string $filename
     * @param callable $progressFunction
     */
    public function download($url, $filename, callable $progressFunction = null)
    {
        $this->client->download($url, $filename, $progressFunction);
    }

    /**
     * Upload an attachment to an issue
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/v2/#api-rest-api-2-issue-issueIdOrKey-attachments-post
     * @param IssueKey $issueKey
     * @param string $attachmentFilePath
     */
    public function addAttachment(IssueKey $issueKey, $attachmentFilePath)
    {
        $this->client->upload(
            sprintf('issue/%s/attachments', $issueKey),
            $attachmentFilePath
        );
    }

    /**
     * @param IssueKey $issueKey
     * @param string $commentString
     * @return Comment
     */
    public function addComment(IssueKey $issueKey, $commentString)
    {
        $response = $this->client->post(
            sprintf('issue/%s/comment', $issueKey),
            [
                'body' => $commentString
            ]
        );
        return Comment::fromArray($this->normaliseDateFields($response));
    }

    /**
     * Retrieve single comment
     *
     * @param IssueKey $issueKey
     * @param CommentId $commentId
     * @return Comment
     */
    public function retrieveComment(IssueKey $issueKey, CommentId $commentId)
    {
        $response = $this->client->get(
            sprintf('issue/%s/comment/%s', $issueKey, $commentId)
        );
        return Comment::fromArray($this->normaliseDateFields($response));
    }

    /**
     * @param IssueKey $issueKey
     * @param CommentId $commentId
     * @param string $comment
     * @return Comment
     */
    public function updateComment(IssueKey $issueKey, CommentId $commentId, $comment)
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
     * @param IssueKey $issueKey
     * @param CommentId $commentId
     * @return bool
     */
    public function deleteComment(IssueKey $issueKey, CommentId $commentId)
    {
        $this->client->delete(sprintf('issue/%s/comment/%s', $issueKey, $commentId));
        return true;
    }

    /**
     * @param string $query Query used to filter issue search results.
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
     * @param IssueKey $inwardIssueKey
     * @param IssueKey $outwardIssueKey
     * @param string $linkName
     * @param string $comment
     * @return IssueLink
     */
    public function linkIssue(IssueKey $inwardIssueKey, IssueKey $outwardIssueKey, $linkName, $comment = '')
    {
        $data = [
            'type' => ['name' => $linkName],
            'inwardIssue' => ['key' => (string) $inwardIssueKey],
            'outwardIssue' => ['key' => (string) $outwardIssueKey],
            'comment' => !empty($comment) ? ['body' => $comment] : false,
        ];

        $this->client->post('issueLink', array_filter($data));

        return IssueLink::fromArray($data);
    }

    /**
     * Returns an issue link with the specified id.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-issueLink-linkId-get
     * @param IssueLinkId $linkId
     * @return IssueLink
     */
    public function retrieveIssueLink(IssueLinkId $linkId)
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
     * @param IssueLinkId $linkId
     * @return bool
     */
    public function removeIssueLink(IssueLinkId $linkId)
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
     * Returns all filters for the current user
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-filter-get
     * @return Filter[]
     */
    public function retrieveFilters()
    {
        return array_map(
            function (array $filter) {
                return Filter::fromArray($filter);
            },
            $this->client->get('filter')
        );
    }

    /**
     * Returns a filter given an id
     *
     * @see https://developer.atlassian.com/cloud/jira/platform/rest/#api-api-2-filter-id-get
     * @param FilterId $filterId
     * @return Filter
     */
    public function retrieveFilter(FilterId $filterId)
    {
        return Filter::fromArray($this->client->get(sprintf('filter/%s', $filterId)));
    }

    /**
     * @param array $jiraIssue
     * @return array
     */
    private function normaliseIssueArray(array $jiraIssue)
    {
        $attachments = isset($jiraIssue['fields']['attachment']) ? $jiraIssue['fields']['attachment'] : [];
        foreach ($attachments as $k => $attachment) {
            $this->replaceAccountIds($attachment);
            $attachments[$k] = $this->normaliseDateFields($attachment);
        }
        $jiraIssue['fields']['attachment'] = $attachments;
        $parent = !empty($jiraIssue['parent']) ? $jiraIssue['parent'] : null;
        if ($parent) {
            $this->replaceAccountIds($parent);
            $jiraIssue['fields']['parent'] = $this->normaliseDateFields($parent);
        }
        $comments = isset($jiraIssue['fields']['comment']) ? $jiraIssue['fields']['comment'] : [];
        if ($comments) {
            foreach ($comments['comments'] as $k => $comment) {
                $this->replaceAccountIds($comment);
                $comments['comments'][$k] = $this->normaliseDateFields($comment);
            }
        }
        $jiraIssue['fields']['comment'] = $comments;
        $worklog = isset($jiraIssue['fields']['worklog']) ? $jiraIssue['fields']['worklog'] : [];
        if ($worklog) {
            foreach ($worklog['worklogs'] as $k => $worklog) {
                $this->replaceAccountIds($worklog);
                $worklog['worklogs'][$k] = $this->normaliseDateFields($worklog);
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

    private function collectAccountIds(array $jiraItem)
    {
        $fields = ['body', 'comment', 'value'];
        $accountIds = [];
        foreach ($jiraItem as $field => $value) {
            if (in_array($field, $fields, true)) {
                if ($numOfMatches = preg_match_all('~(\[\~)(accountid:([^]]+))(\])~smu', $value, $matches)) {
                    for ($i = 0; $i < $numOfMatches; $i++) {
                        $accountIds[] = $matches[3][$i];
                    }
                }
            }
        }

        return array_filter(array_unique($accountIds));
    }

    private function replaceAccountIds(array &$jiraItem)
    {
        $fields = ['body', 'comment'];
        $accountIds = $this->collectAccountIds($jiraItem);
        if (empty($accountIds)) {
            return;
        }

        $users = $this->users($accountIds);
        foreach ($jiraItem as $field => $value) {
            if (in_array($field, $fields, true)) {
                if ($numOfMatches = preg_match_all('~(\[\~)([^]]+)(\])~smu', $value, $matches)) {
                    for ($i = 0; $i < $numOfMatches; $i++) {
                        $username = $matches[2][$i];
                        foreach ($users as $user) {
                            if ('accountid:' . $user->id() === $matches[2][$i]) {
                                $username = $user->displayName();
                            }
                        }
                        $value = str_replace(
                            $matches[1][$i].$matches[2][$i].$matches[3][$i],
                            '[~' . $username . ']',
                            $value
                        );
                    }
                    $jiraItem[$field] = $value;
                }
            }
        }
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
