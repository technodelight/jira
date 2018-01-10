<?php

namespace Technodelight\Jira\Api\Shell;

interface Shell
{
    /**
     * @param Command $command
     * @throws ShellCommandException
     * @return array
     */
    public function exec(Command $command);
}
