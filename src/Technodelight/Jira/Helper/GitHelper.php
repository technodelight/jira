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

    public function commitMessages()
    {
        // $parent = show-branch -a | sed 's/^ *//g' | grep -v "^\*" | head -1 | sed 's/.*\[\(.*\)\].*/\1/' | sed 's/[\^~].*//'
        $parentCommit = implode(PHP_EOL, $this->shell('show-branch -a'));
        if (preg_match('~\[([^\]]+)\]~', $parentCommit, $matches)) {
            return $this->shell('log ' . $matches[1] . '..head --format=%s --no-merges');
        }

        return [];
    }

    private function shell($command)
    {
        $result = explode(PHP_EOL, shell_exec("git $command"));
        return array_filter(array_map('trim', $result));
    }
}
