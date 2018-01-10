<?php

namespace Technodelight\Jira\Api\Shell;

use RuntimeException;
use Throwable;

class ShellCommandException extends RuntimeException
{
    const MESSAGE = 'Error code %d during running "%s"';

    private $result;

    public static function fromDetails(Command $command, $errorCode, array $result = [], Throwable $previous = null)
    {
        return new self(
            sprintf(self::MESSAGE, $errorCode, $command),
            $errorCode,
            $previous,
            $result
        );
    }

    public static function fromCommandAndErrorCode(Command $command, $errorCode, Throwable $previous = null)
    {
        return new self(
            sprintf(self::MESSAGE, $errorCode, $command),
            $errorCode,
            $previous
        );
    }

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
