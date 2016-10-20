<?php

namespace Technodelight\Jira\Api\Shell;

class Shell
{
    private $executable;

    public function __construct($executable)
    {
        $this->executable = $executable;
    }

    public function exec($command)
    {
        $shellCommand = sprintf('%s %s', $this->executable, $command);
        exec($shellCommand, $result, $returnVar);
        if (!$returnVar) {
            throw new \RuntimeException(
                sprintf(
                    'Error code %d during running "%s"',
                    $returnVar,
                    $shellCommand
                )
            );
        }
        return array_filter(array_map('trim', $result));
    }
}
