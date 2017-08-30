<?php

namespace Technodelight\Jira\Helper;

use Hoa\Console\Readline\Autocompleter\Word;
use Hoa\Console\Readline\Readline;
use Technodelight\Jira\Api\Issue;

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

    /**
     * @param \Technodelight\Jira\Api\Issue $issue
     * @return string
     */
    public function fromIssueWithAutocomplete(Issue $issue)
    {
        $readline = new Readline;
        $readline->setAutocompleter(new Word($this->getAutocompleteWords($issue)));
        $branchName = $readline->readLine(sprintf(
            $this->jiraPattern,
            $issue->ticketNumber(),
            ''
        ));
        return sprintf($this->jiraPattern, $issue->ticketNumber(), $branchName);
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

    /**
     * @param \Technodelight\Jira\Api\Issue $issue
     * @return array
     */
    private function getAutocompleteWords(Issue $issue)
    {
        return explode($this->separator, strtolower($this->replace($this->remove($issue->summary()))));
    }
}
