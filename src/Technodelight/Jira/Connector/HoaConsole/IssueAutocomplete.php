<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\HoaConsole;

use Technodelight\Jira\Domain\IssueCollection;

class IssueAutocomplete implements Autocompleter
{
    private IssueCollection $issues;

    public function __construct(IssueCollection $issues)
    {
        $this->issues = $issues;
    }

    public function complete($prefix): ?array
    {
        $matching = [];
        foreach ($this->issues as $issue) {
            if (empty(ltrim($prefix, '#'))) {
                $matching[] = $issue->key();
            } else
            if (str_contains((string)$issue->key(), ltrim($prefix, '#'))) {
                $matching[] = $issue->key();
            }
        }
        if (count($matching) === 1) {
            return end($matching);
        }

        return count($matching) === 0 ? null : $matching;
    }

    public function getWordDefinition(): string
    {
        return '\#[A-Z0-9-]*';
    }
}
