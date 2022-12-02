<?php

namespace Technodelight\Jira\Connector\Jira;

use DateTime;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Connector\WorklogHandler as WorklogHandlerInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\Worklog\WorklogId;
use Technodelight\Jira\Domain\WorklogCollection;

class WorklogHandler implements WorklogHandlerInterface
{
    /**
     * @var Api
     */
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @return WorklogCollection
     */
    public function find(DateTime $from, DateTime $to): WorklogCollection
    {
        $issues = $this->api->findUserIssuesWithWorklogs($from, $to, $this->api->user());

        $worklogCollection = WorklogCollection::createEmpty();
        foreach ($issues as $issue) {
            $worklogCollection->merge($issue->worklogs());
        }
        return $worklogCollection;
    }

    /**
     * @param Issue $issue
     * @param null $limit
     * @return WorklogCollection
     */
    public function findByIssue(Issue $issue, $limit = null): WorklogCollection
    {
        return $this->api->retrieveIssueWorklogs($issue->key(), $limit);
    }

    /**
     * @param Worklog $worklog
     * @return Worklog
     */
    public function create(Worklog $worklog): Worklog
    {
        return $this->api->createWorklog($worklog);
    }

    /**
     * @param Worklog $worklog
     * @return Worklog
     */
    public function update(Worklog $worklog): Worklog
    {
        return $this->api->updateWorklog($worklog);
    }

    /**
     * @param int $worklogId
     * @return Worklog
     */
    public function retrieve($worklogId): Worklog
    {
        return $this->api->retrieveWorklogs([WorklogId::fromString($worklogId)])->current();
    }

    /**
     * @param Worklog $worklog
     * @return bool
     */
    public function delete(Worklog $worklog): bool
    {
        $this->api->deleteWorklog($worklog);
        return true;
    }
}
