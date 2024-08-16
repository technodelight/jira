<?php

declare(strict_types=1);

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
    public function __construct(private readonly Api $api) {}

    /**
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function find(DateTime $from, DateTime $to): WorklogCollection
    {
        $issues = $this->api->findUserIssuesWithWorklogs($from, $to, $this->api->user());

        $workLogCollection = WorklogCollection::createEmpty();
        foreach ($issues as $issue) {
            $workLogCollection->merge($issue->worklogs());
        }
        return $workLogCollection;
    }

    public function findByIssue(Issue $issue, ?int $limit = null): WorklogCollection
    {
        return $this->api->retrieveIssueWorklogs($issue->key(), $limit);
    }

    public function create(Worklog $worklog): Worklog
    {
        return $this->api->createWorklog($worklog);
    }

    public function update(Worklog $worklog): Worklog
    {
        return $this->api->updateWorklog($worklog);
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    public function retrieve(int $worklogId): Worklog
    {
        return $this->api->retrieveWorklogs([WorklogId::fromNumeric($worklogId)])->current();
    }

    public function delete(Worklog $worklog): bool
    {
        $this->api->deleteWorklog($worklog);
        return true;
    }
}
