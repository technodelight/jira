<?php

namespace Technodelight\Jira\Console\IssueStats;

class StringParser
{
    const ISSUE_PATTERN = '~([A-Z]+-[0-9]+)~';

    /**
     * @param string $string
     * @return array
     */
    public static function parse($string)
    {
        if (preg_match_all(self::ISSUE_PATTERN, $string, $matches)) {
            return $matches[1];
        }
        return [];
    }
}
