<?php

namespace Technodelight\Jira\Connector\Jira;

use DateTime;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Connector\WorklogHandler as WorklogHandlerInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;
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
    public function find(DateTime $from, DateTime $to)
    {
        $issues = $this->api->findUserIssuesWithWorklogs($from, $to, $this->api->user()->name());

        $worklogCollection = WorklogCollection::createEmpty();
        foreach ($issues as $issue) {
            $worklogCollection->merge($issue->worklogs());
        }
        return $worklogCollection;
    }

    /**
     * @param Issue $issue
     * @return WorklogCollection
     */
    public function findByIssue(Issue $issue)
    {
        return $this->api->retrieveIssueWorklogs($issue->key());
    }

    /**
     * @param Worklog $worklog
     * @return Worklog
     */
    public function create(Worklog $worklog)
    {
        return $this->api->worklog(
            $worklog->issueKey(),
            $worklog->timeSpentSeconds(),
            $worklog->comment(),
            $worklog->date()->format('Y-m-d H:i:s')
        );
    }

    /**
     * @param Worklog $worklog
     * @return Worklog
     */
    public function update(Worklog $worklog)
    {
        return $this->api->updateWorklog($worklog);
    }

    /**
     * @param int $worklogId
     * @return Worklog
     */
    public function retrieve($worklogId)
    {
        return $this->api->retrieveWorklogs([$worklogId])->current();
    }

    /**
     * @param Worklog $worklog
     * @return bool
     */
    public function delete(Worklog $worklog)
    {
        $this->api->deleteWorklog($worklog);
        return true;
    }
}
