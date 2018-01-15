<?php

namespace Technodelight\Jira\Api\OpenApp;

use RuntimeException;
use Throwable;

class Exception extends RuntimeException
{
    const MESSAGE = 'Cannot open %s';

    public static function fromUri($uri, Throwable $previous = null)
    {
        return new self(
            sprintf(self::MESSAGE, $uri),
            0,
            $previous
        );
    }
}
