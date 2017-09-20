<?php

namespace Technodelight\Jira\Api\Shell;

interface Shell
{
    /**
     * @param \Technodelight\Jira\Api\Shell\Command $command
     * @return array
     * @throws \RuntimeException
     */
    public function exec(Command $command);
}
