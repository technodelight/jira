<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Argument;

use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\Worklog\WorklogId;

class IssueKeyOrWorklogId
{
    private ?WorklogId $worklogId = null;
    private ?IssueKey $issueKey = null;
    private ?Worklog $worklog = null;

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    public static function fromString($string): self
    {
        $instance = new self;
        if (is_numeric($string)) {
            $instance->worklogId = WorklogId::fromNumeric($string);
        } elseif(!empty($string)) {
            $instance->issueKey = IssueKey::fromString($string);
        }
        return $instance;
    }

    public static function fromWorklog(Worklog $worklog): self
    {
        $instance = new self;
        $instance->worklogId = $worklog->id();
        $instance->issueKey = $worklog->issueKey();
        $instance->worklog = $worklog;
        return $instance;
    }

    public function issueKey(): ?IssueKey
    {
        return $this->issueKey;
    }

    public function worklogId(): ?WorklogId
    {
        return $this->worklogId;
    }

    public function isWorklogId(): bool
    {
        return !empty($this->worklogId);
    }

    public function isIssueKey(): bool
    {
        return !empty($this->issueKey);
    }

    public function isEmpty(): bool
    {
        return empty($this->worklogId) && empty($this->issueKey);
    }

    public function worklog(): ?Worklog
    {
        return $this->worklog;
    }
}
