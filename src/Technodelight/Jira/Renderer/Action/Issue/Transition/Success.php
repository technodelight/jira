<?php

namespace Technodelight\Jira\Renderer\Action\Issue\Transition;

use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Renderer\Action\Result;

class Success implements Result
{
    private IssueKey $issueKey;
    private string $phrase;
    private array $data = [];

    public static function fromIssueKeyAndAssignee(IssueKey $issueKey, $transition, $assignee = null): Success
    {
        $instance = new self;
        $instance->issueKey = $issueKey;
        $instance->phrase = match($assignee) {
            null => '%s has been successfully moved to %s',
            false => '%s has been successfully moved to %s and has been unassigned',
            default => '%s has been successfully moved to %s and has been assigned to %s'
        };
        $instance->data = array_filter([$transition, $assignee]);

        return $instance;
    }

    public function issueKey(): IssueKey
    {
        return $this->issueKey;
    }

    public function phrase(): string
    {
        return $this->phrase;
    }

    public function data(): array
    {
        return $this->data;
    }
}
