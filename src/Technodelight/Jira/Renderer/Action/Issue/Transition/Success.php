<?php

namespace Technodelight\Jira\Renderer\Action\Issue\Transition;

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

    public static function fromIssueKeyAndAssignee(IssueKey $issueKey, $transition, $assignee = null)
    {
        $instance = new self;
        $instance->issueKey = $issueKey;
        if ($assignee === null) {
            $instance->phrase = '%s has been successfully moved to %s';
            $instance->data = [$transition];
        } else if ($assignee === false) {
            $instance->phrase = '%s has been successfully moved to %s and has been unassigned';
            $instance->data = [$transition];
        } else {
            $instance->phrase = '%s has been successfully moved to %s and has been assigned to %s';
            $instance->data = [$transition, $assignee];
        }

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
