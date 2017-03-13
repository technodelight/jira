<?php

namespace Fixture\Shell;

use Technodelight\Jira\Api\Shell\Command;
use Technodelight\Jira\Api\Shell\Shell;

class TestShell implements Shell
{
    const ERROR_NO_SUCH_FIXTURE = 'No such fixture: "%s"';
    public static $fixtures = [];
    private $exec;

    public function __construct($exec = null)
    {
        $this->exec = $exec;
    }

    public function exec(Command $command)
    {
        if (isset(self::$fixtures[(string) $command])) {
            return self::$fixtures[(string) $command];
        }
        throw new \InvalidArgumentException(sprintf(self::ERROR_NO_SUCH_FIXTURE, $command));
    }
}
