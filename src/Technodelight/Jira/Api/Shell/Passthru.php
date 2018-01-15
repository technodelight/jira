<?php

namespace Technodelight\Jira\Api\Shell;

class Passthru implements Shell
{
    /**
     * @var
     */
    private $executable;

    public function __construct($executable = null)
    {
        $this->executable = $executable;
    }

    /**
     * @param \Technodelight\Jira\Api\Shell\Command $command
     * @return array
     * @throws \RuntimeException
     */
    public function exec(Command $command)
    {
        if ($this->executable) {
            $command->withExec($this->executable);
        }

        passthru((string) $command, $returnVar);
        if (!empty($returnVar)) {
            throw ShellCommandException::fromCommandAndErrorCode($command, $returnVar);
        }

        return [];
    }
}
