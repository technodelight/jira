<?php

namespace Technodelight\Jira\Helper;

class AutocompleteHelper
{
    public function getWords(array $texts)
    {
        $words = array_map(
            function($string) {
                return trim(trim($string, '-,'.PHP_EOL));
            },
            explode(' ', join(' ', $texts))
        );
        return array_unique(array_filter($words));
    }
}
