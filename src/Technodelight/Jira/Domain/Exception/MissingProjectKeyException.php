<?php

namespace Technodelight\Jira\Domain\Exception;

class MissingProjectKeyException extends \UnexpectedValueException implements ArgumentException
{
    protected $message = 'The "ProjectKey" parameter is missing';
}
