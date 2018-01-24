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
    /**
     * @var string|array|null
     */
    private $history;

    public function __construct(Issue $issue, IssueCollection $issues = null, array $texts = [], $history = null)
    {
        $this->issue = $issue;
        $this->issues = $issues;
        $this->words = $this->parseWords($texts);
        $this->history = $history;
    }

    public function getValue($prefix = null)
    {
        $readline = $this->getReadline($this->issue, $this->issues, $this->words, $this->history);
        return preg_replace('~^<new>~', '', $readline->readLine($prefix));
    }

    public function helpText()
    {
        return '(Ctrl-A: beginning of the line, Ctrl-E: end of the line, Ctrl-B: backward one word, Ctrl-F: forward one word, Ctrl-W: delete first backward word)';
    }

    private function getReadline(Issue $issue, IssueCollection $issues = null, array $words = [], $history = null)
    {
        $readline = new Readline;
        if (!is_array($history)) {
            $history = [$history];
        }
        if (is_array($history)) {
            foreach ($history as $historyItem) {
                $readline->addHistory($historyItem);
            }
        }
        $readline->addHistory('<new>');
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
