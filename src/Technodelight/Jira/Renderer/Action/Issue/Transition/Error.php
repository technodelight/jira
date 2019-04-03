<?php

namespace Technodelight\Jira\Renderer\Action\Issue\Transition;

use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Renderer\Action\Error as BaseError;

class Error extends BaseError
{
    /**
     * @var IssueKey
     */
    private $issueKey;

    public static function fromExceptionIssueKeyTransitions(\Exception $exception, IssueKey $issueKey, $transitions)
    {
        $instance = new self;
        $instance->exception = $exception;
        $instance->issueKey = $issueKey;
        $instance->phrase = '%s cannot be moved on any of the following transitions: %s';
        $instance->data = [join(', ', $transitions)];

        return $instance;
    }

    public function issueKey(): IssueKey
    {
        return $this->issueKey;
    }
}
