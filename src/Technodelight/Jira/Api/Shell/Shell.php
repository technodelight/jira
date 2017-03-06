<?php

namespace Technodelight\Jira\Api\Shell;

interface Shell
{
    public function exec(Command $command);
}
