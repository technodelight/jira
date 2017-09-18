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
    public function retrieve(DateTime $from, DateTime $to);

    /**
     * @param Issue $issue
     * @param Worklog $worklog
     * @return Worklog
     */
    public function create(Issue $issue, Worklog $worklog);

    /**
     * @param Worklog $worklog
     * @return Worklog
     */
    public function update(Worklog $worklog);
}
