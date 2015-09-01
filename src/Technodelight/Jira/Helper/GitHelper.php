<?php

namespace Technodelight\Jira\Helper;

class GitHelper
{
    public function branches($pattern = '')
    {
        $command = 'branch' . ($pattern ? sprintf('| grep "%s"', $pattern) : '');
        return $this->shell($command);
    }

    public function currentBranch()
    {
        $list = $this->branches('* ');
        return ltrim(end($list), '* ');
    }

    public function topLevelDirectory()
    {
        $tld = $this->shell('rev-parse --show-toplevel');
        return trim(end($tld));
    }

    private function shell($command)
    {
        $result = explode(PHP_EOL, shell_exec("git $command"));
        return array_filter(array_map('trim', $result));
    }
}
