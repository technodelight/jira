<?php

namespace Technodelight\Jira\Console\Argument;

use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\Worklog\WorklogId;

class IssueKeyOrWorklogId
{
    private $worklogId;
    private $issueKey;
    private $worklog;

    public static function fromString($string)
    {
        $instance = new self;
        if (intval($string)) {
            $instance->worklogId = WorklogId::fromString($string);
        } elseif(!empty($string)) {
            $instance->issueKey = IssueKey::fromString($string);
        }
        return $instance;
    }

    public static function fromWorklog(Worklog $worklog)
    {
        $instance = new self;
        $instance->worklogId = $worklog->id();
        $instance->issueKey = $worklog->issueKey();
        $instance->worklog = $worklog;
        return $instance;
    }

    public function issueKey()
    {
        return $this->issueKey;
    }

    public function worklogId()
    {
        return $this->worklogId;
    }

    public function isWorklogId()
    {
        return !empty($this->worklogId);
    }

    public function isIssueKey()
    {
        return !empty($this->issueKey);
    }

    public function isEmpty()
    {
        return empty($this->worklogId) && empty($this->issueKey);
    }

    /**
     * @return Worklog|null
     */
    public function worklog()
    {
        return $this->worklog;
    }
}
