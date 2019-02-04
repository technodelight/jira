<?php

namespace Technodelight\Jira\Domain\Exception;

class MissingIssueKeyException extends \UnexpectedValueException implements ArgumentException
{
    protected $message = 'The "IssueKey" parameter is missing';
}
