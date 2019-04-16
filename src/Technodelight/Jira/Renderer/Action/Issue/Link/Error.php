<?php

namespace Technodelight\Jira\Renderer\Action\Issue\Link;

use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Renderer\Action\Error as BaseError;

class Error extends BaseError
{
    /**
     * @var IssueKey
     */
    private $issueKey;

    public static function fromExceptionAndIssueKey(\Exception $exception, IssueKey $issueKey)
    {
        $instance = new self;
        $instance->exception = $exception;
        $instance->issueKey = $issueKey;
        $instance->phrase = '%s cannot be linked';

        return $instance;
    }

    public function issueKey(): IssueKey
    {
        return $this->issueKey;
    }
}
