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

    private function getReadline(Issue $issue, IssueCollection $issues = null, array $words = [])
    {
        $readline = new Readline;
        $readline->setAutocompleter(
            $this->getAggregateAutocomplete($issue, $issues, $words)
        );
        return $readline;
    }

    private function parseWords(array $texts)
    {
        $words = [];
        foreach ($texts as $text) {
            $text = preg_replace('~[^a-zA-Z0-9\s\']+~', '', $text);
            $words = array_merge($words, array_map('strtolower', preg_split('~\s~', $text)));
        }
        return array_filter(array_map('trim', array_unique($words)));
    }

    /**
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @param \Technodelight\Jira\Domain\IssueCollection $issues
     * @param array $words
     * @return \Hoa\Console\Readline\Autocompleter\Aggregate
     */
    private function getAggregateAutocomplete(Issue $issue, IssueCollection $issues = null, array $words = [])
    {
        $autocompleters = [
            $words ? new Word(array_unique($words)) : null,
            new UsernameAutocomplete($issue),
            !is_null($issues) ? new IssueAutocomplete($issues) : null
        ];

        return new Aggregate(array_filter($autocompleters));
    }
}
