<?php

namespace Technodelight\Jira\Helper;

class GitHelper extends ShellCommandHelper
{
    public function log($pattern)
    {
// log
//      --format="<hash><![CDATA[%H]]></hash><message><![CDATA[%B]]></message><authorName>%aN</authorName><authorDate>%at</authorDate>"
//      --no-merges
//      --date-order
//      --reverse
//      <parent>..<head sha1>

    }

    public function createBranch($branchName)
    {
        $this->shell(sprintf('checkout -b %s', $branchName));
    }

    public function switchBranch($branchName)
    {
        $this->shell(sprintf('checkout %s', $branchName));
    }

    public function branches($pattern = '')
    {
        return array_map(
            function($row) {
                return str_replace('remotes/', '', $row);
            },
            $this->shell('branch -a ' . ($pattern ? sprintf('| grep "%s"', $pattern) : ''))
        );
    }

    public function currentBranch()
    {
        $list = $this->branches('* ');
        return ltrim(end($list), '* ');
    }

    public function issueKeyFromCurrentBranch()
    {
        if (preg_match('~^feature/([A-Z]+[0-9]+)-(.*)~', $this->currentBranch(), $matches)) {
            return $matches[1];
        }

        return '';
    }

    public function topLevelDirectory()
    {
        $tld = $this->shell('rev-parse --show-toplevel');
        return trim(end($tld));
    }

    public function commitMessages()
    {
        // $parent = show-branch -a | sed 's/^ *//g' | grep -v "^\*" | head -1 | sed 's/.*\[\(.*\)\].*/\1/' | sed 's/[\^~].*//'
        $parentCommit = implode(PHP_EOL, $this->shell('show-branch -a 2> /dev/null'));
        if (preg_match('~\[([^\]]+)\]~', $parentCommit, $matches)) {
            return $this->shell('log ' . $matches[1] . '..head --format=%s --no-merges');
        }

        return [];
    }

    public function getName()
    {
        return 'git';
    }

    protected function getExecutable()
    {
        return '/usr/bin/env git';
    }
}
