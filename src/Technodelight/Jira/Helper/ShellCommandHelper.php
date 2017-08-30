<?php

namespace Technodelight\Jira\Helper;

use Symfony\Component\Console\Helper\Helper;

/**
 * @deprecated
 */
abstract class ShellCommandHelper extends Helper
{
    abstract protected function getExecutable();

    protected function shell($command)
    {
        $result = explode(PHP_EOL, shell_exec("{$this->getExecutable()} $command"));
        return array_filter(array_map('trim', $result));
    }
}
