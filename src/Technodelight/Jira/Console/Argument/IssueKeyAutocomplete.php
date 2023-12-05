<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Argument;

use Technodelight\Jira\Console\IssueStats\StatCollector;

class IssueKeyAutocomplete
{
    public function __construct(
        private readonly StatCollector $statCollector
    ) {
    }

    public function autocomplete(string $buffer): array
    {
        $issueKeys = array_slice(array_filter(
            $this->statCollector->all()->orderByMostRecent()->issueKeys(),
            static function (string $issueKey) use ($buffer) {
                return str_starts_with(
                    strtolower($issueKey),
                    strtolower($buffer)
                );
            }
        ), 0, 10);
        usort($issueKeys, fn($a, $b) => $a <=> $b);

        return $issueKeys;
    }
}
