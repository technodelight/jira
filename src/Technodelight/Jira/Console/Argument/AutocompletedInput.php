<?php

namespace Technodelight\Jira\Console\Argument;

use Hoa\Console\Readline\Autocompleter\Aggregate;
use Hoa\Console\Readline\Autocompleter\Word;
use Hoa\Console\Readline\Readline;
use Technodelight\Jira\Console\HoaConsole\IssueAutocomplete;
use Technodelight\Jira\Console\HoaConsole\UsernameAutocomplete;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;

class AutocompletedInput
{
    /**
     * @var \Technodelight\Jira\Domain\Issue
     */
    private $issue;
    /**
     * @var array
     */
    private $words;
    /**
     * @var \Technodelight\Jira\Domain\IssueCollection
     */
    private $issues;

    public function __construct(Issue $issue, IssueCollection $issues = null, array $texts = [])
    {
        $this->issue = $issue;
        $this->issues = $issues;
        $this->words = $this->parseWords($texts);
    }

    public function getValue($prefix = null)
    {
        $readline = $this->getReadline($this->issue, $this->issues, $this->words);
        return $readline->readLine($prefix);
    }

    private function getReadline(Issue $issue, IssueCollection $issues, array $words)
    {
        $readline = new Readline;
        $readline->setAutocompleter(
            $this->getAggregateAutocomplete($issue, $issues, $words)
        );
        return $readline;
    }

    private function parseWords(array $texts)
    {
        $words = array_map(
            function($string) {
                return trim(trim($string, '-,'.PHP_EOL));
            },
            explode(' ', join(' ', $texts))
        );
        return array_unique(array_filter($words));
    }

    /**
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @param \Technodelight\Jira\Domain\IssueCollection $issues
     * @param array $words
     * @return \Hoa\Console\Readline\Autocompleter\Aggregate
     */
    private function getAggregateAutocomplete(Issue $issue, IssueCollection $issues, array $words)
    {
        $autocompleters = [
            new Word($words),
            new UsernameAutocomplete($issue)
        ];
        if (!is_null($issues)) {
            $autocompleters[] = new IssueAutocomplete($issues);
        }

        return new Aggregate($autocompleters);
    }
}
