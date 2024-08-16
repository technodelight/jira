<?php

namespace Technodelight\Jira\Renderer\Action\Issue\Assign;

use \Exception;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Renderer\Action\Error as BaseError;

class Error extends BaseError
{
    /**
     * @var IssueKey
     */
    private $issueKey;

    public static function fromExceptionIssueKeyAndAssignee(Exception $exception, IssueKey $issueKey, $assignee = null)
    {
        $instance = new self;
        $instance->exception = $exception;
        $instance->issueKey = $issueKey;
        $instance->data = [$assignee];
        $instance->phrase = match(true) {
            !empty($assignee) => '%s cannot be assigned to %s',
            default => '%s cannot be unassigned'
        };

        return $instance;
    }

    public function issueKey(): IssueKey
    {
        return $this->issueKey;
    }
}
