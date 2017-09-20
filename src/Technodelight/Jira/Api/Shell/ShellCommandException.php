<?php

namespace Technodelight\Jira\Api\Shell;

use RuntimeException;
use Throwable;

class ShellCommandException extends RuntimeException
{
    private $result;

    public function __construct($message = "", $code = 0, Throwable $previous = null, array $result = [])
    {
        parent::__construct($message, $code, $previous);
        $this->result = $result;
    }

    public function getResult()
    {
        return $this->result;
    }
}
