<?php

namespace Technodelight\Jira\Renderer\Action\Issue\Assign;

use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Renderer\Action\Error as BaseError;

class Error extends BaseError
{
    /**
     * @var IssueKey
     */
    private $issueKey;

    public static function fromExceptionIssueKeyAndAssignee(\Exception $exception, IssueKey $issueKey, $assignee = null)
    {
        $instance = new self;
        $instance->exception = $exception;
        $instance->issueKey = $issueKey;
        if ($assignee) {
            $instance->phrase = '%s cannot be assigned to %s';
            $instance->data = [$assignee];
        } else {
            $instance->phrase = '%s cannot be unassigned';
        }

        return $instance;
    }

    public function issueKey(): IssueKey
    {
        return $this->issueKey;
    }
}
