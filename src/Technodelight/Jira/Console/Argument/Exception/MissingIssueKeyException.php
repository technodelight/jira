<?php

namespace Technodelight\Jira\Console\Argument\Exception;

class MissingIssueKeyException extends \UnexpectedValueException implements ArgumentException
{
    protected $message = 'The "IssueKey" parameter is missing';
}
