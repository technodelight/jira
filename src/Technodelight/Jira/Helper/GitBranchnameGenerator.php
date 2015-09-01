<?php

namespace Technodelight\Jira\Helper;

class GitBranchnameGenerator
{
    private $remove = ['BE', 'FE'];
    private $replace = [' ', ':', '/', ','];
    private $jiraPattern = 'feature/%s-%s';
    private $separator = '-';

    public function fromIssue(Issue $issue)
    {
        return $this->cleanup(
            sprintf(
                $this->jiraPattern,
                $issue->ticketNumber(),
                strtolower($this->replace($this->remove($issue->summary())))
            )
        );
    }

    private function remove($summary)
    {
        return str_replace($this->remove, '', $summary);
    }

    private function replace($summary)
    {
        return str_replace($this->replace, $this->separator, $summary);
    }

    private function cleanup($branchName)
    {
        $branchName = preg_replace('~[^A-Za-z0-9/-]~', '', $branchName);
        return preg_replace(
            '~[' . preg_quote($this->separator) . ']+~',
            $this->separator,
            trim($branchName, $this->separator)
        );
    }
}
