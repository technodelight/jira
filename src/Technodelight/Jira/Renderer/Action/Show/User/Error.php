<?php

namespace Technodelight\Jira\Renderer\Action\Show\User;

use Technodelight\Jira\Renderer\Action\Error as BaseError;

class Error extends BaseError
{
    public static function fromExceptionAndAccountId(\Exception $exception, $accountId)
    {
        $instance = new self;
        $instance->exception = $exception;
        $instance->phrase = $accountId ? '%s cannot be found' : 'accountId is missing';
        $instance->data = [$accountId];

        return $instance;
    }
}
