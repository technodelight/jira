<?php

namespace Technodelight\Jira\Console\Argument\Exception;

class MissingProjectKeyException extends \UnexpectedValueException implements ArgumentException
{
    protected $message = 'The "ProjectKey" parameter is missing';
}
