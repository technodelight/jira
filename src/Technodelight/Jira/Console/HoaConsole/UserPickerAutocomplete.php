<?php

namespace Technodelight\Jira\Console\HoaConsole;

use Hoa\Console\Readline\Autocompleter\Autocompleter;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\UserPickerResult;

class UserPickerAutocomplete implements Autocompleter
{
    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private $jira;

    public function __construct(Api $jira)
    {
        $this->jira = $jira;
    }

    /**
     * Complete a word.
     * Returns null for no word, a full-word or an array of full-words.
     *
     * @param   string &$prefix Prefix to autocomplete.
     * @return  mixed
     */
    public function complete(&$prefix)
    {
        if (!empty($prefix)) {
            $results = array_map(
                function(UserPickerResult $user) {
                    return $user->key();
                },
                $this->jira->userPicker($prefix)
            );
            return count($results) > 1 ? $results : current($results);
        }
    }

    /**
     * Get definition of a word.
     * Example: \b\w+\b. PCRE delimiters and options must not be provided.
     *
     * @return  string
     */
    public function getWordDefinition()
    {
        return '[a-zA-Z0-9.-]+';
    }
}
