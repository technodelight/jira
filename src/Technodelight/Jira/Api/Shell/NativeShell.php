<?php

namespace Technodelight\Jira\Api\Shell;

class NativeShell implements Shell
{
    private $executable;

    public function __construct($executable = null)
    {
        $this->executable = $executable;
    }

    public function exec(Command $command)
    {
        if ($this->executable) {
            $command->withExec($this->executable);
        }
        exec((string) $command, $result, $returnVar);
        if (!empty($returnVar)) {
            throw new \RuntimeException(
                sprintf(
                    'Error code %d during running "%s"',
                    $returnVar,
                    $command
                )
            );
        }
        return array_filter(array_map('trim', $result));
    }
}
