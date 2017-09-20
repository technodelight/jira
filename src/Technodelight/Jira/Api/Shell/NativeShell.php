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
        $result = array_filter(array_map('trim', $result));
        if (!empty($returnVar)) {
            throw new ShellCommandException(
                sprintf(
                    'Error code %d during running "%s"',
                    $returnVar,
                    $command
                ),
                $returnVar,
                null,
                $result
            );
        }
        return $result;
    }
}
