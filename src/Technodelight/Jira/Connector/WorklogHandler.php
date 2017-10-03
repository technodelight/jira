<?php

namespace Technodelight\Jira\Connector;

use DateTime;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\WorklogCollection;

interface WorklogHandler
{
    /**
     * @param DateTime $from
     * @param DateTime $to
     * @return WorklogCollection
     */
    public function find(DateTime $from, DateTime $to);

    /**
     * @param Issue $issue
     * @return WorklogCollection
     */
    public function findByIssue(Issue $issue);

    /**
     * @param Worklog $worklog
     * @return Worklog
     */
    public function create(Worklog $worklog);

    /**
     * @param Worklog $worklog
     * @return Worklog
     */
    public function update(Worklog $worklog);

    /**
     * @param int $worklogId
     * @return Worklog
     */
    public function retrieve($worklogId);

    /**
     * @param \Technodelight\Jira\Domain\Worklog $worklog
     * @return bool
     */
    public function delete(Worklog $worklog);
}
