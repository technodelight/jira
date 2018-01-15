<?php

namespace Technodelight\Jira\Api\Shell;

class Exec implements Shell
{
    private $executable;

    public function __construct($executable = null)
    {
        $this->executable = $executable;
    }

    /**
     * @param \Technodelight\Jira\Api\Shell\Command $command
     * @return array
     */
    public function exec(Command $command)
    {
        if ($this->executable) {
            $command->withExec($this->executable);
        }
        exec((string) $command, $result, $returnVar);
        $result = (array) array_filter(array_map('trim', (array) $result));
        if (!empty($returnVar)) {
            throw ShellCommandException::fromDetails($command, $returnVar, $result);
        }
        return $result;
    }
}
