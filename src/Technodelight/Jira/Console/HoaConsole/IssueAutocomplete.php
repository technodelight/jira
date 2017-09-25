<?php

namespace Technodelight\Jira\Console\HoaConsole;

use Hoa\Console\Readline\Autocompleter\Autocompleter;
use Technodelight\Jira\Domain\IssueCollection;

class IssueAutocomplete implements Autocompleter
{

    /**
     * @var \Technodelight\Jira\Domain\IssueCollection
     */
    private $issues;

    public function __construct(IssueCollection $issues)
    {
        $this->issues = $issues;
    }

    /**
     * Complete a word.
     * Returns null for no word, a full-word or an array of full-words.
     *
     * @param   string &$prefix Prefix to autocomplete.
     * @return  string|array|null
     */
    public function complete(&$prefix)
    {
        $matching = [];
        foreach ($this->issues as $issue) {
            if (empty(ltrim($prefix, '#'))) {
                $matching[] = $issue->key();
            } else
            if (strpos($issue->key(), ltrim($prefix, '#')) !== false) {
                $matching[] = $issue->key();
            }
        }
        if (count($matching) == 1) {
            return end($matching);
        }
        return count($matching) == 0 ? null : $matching;
    }

    /**
     * Get definition of a word.
     * Example: \b\w+\b. PCRE delimiters and options must not be provided.
     *
     * @return  string
     */
    public function getWordDefinition()
    {
        return '\#[A-Z0-9-]*';
    }
}
