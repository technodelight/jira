<?php

namespace Technodelight\Jira\Renderer\Action\Issue\Assign;

use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Renderer\Action\Result;

class Success implements Result
{
    /**
     * @var IssueKey
     */
    private $issueKey;
    private $phrase;
    private $data = [];

    public static function fromIssueKeyAndAssignee(IssueKey $issueKey, $assignee = null)
    {
        $instance = new self;
        $instance->issueKey = $issueKey;
        $instance->data = [$assignee];
        $instance->phrase = match(true) {
            !empty($assignee) => '%s was assigned successfully to %s',
            default => '%s was unassigned successfully'
        };

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
