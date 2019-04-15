<?php

namespace Technodelight\Jira\Renderer\Action\Show\User;

use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Renderer\Action\Error as BaseError;

class Error extends BaseError
{
    /**
     * @var IssueKey
     */
    private $issueKey;

    public static function fromExceptionAndAccountId(\Exception $exception, $accountId)
    {
        $instance = new self;
        $instance->exception = $exception;
        $instance->phrase = '%s cannot be found';
        $instance->data = [$accountId];

        return $instance;
    }

    public function issueKey(): IssueKey
    {
        return $this->issueKey;
    }
}
