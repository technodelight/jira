<?php

namespace Technodelight\Jira\Connector\HoaConsole;

use Hoa\Console\Readline\Autocompleter\Autocompleter;
use Technodelight\Jira\Console\Input\Issue\Assignee\AssigneeResolver;

class DefaultUsersAutocomplete implements Autocompleter
{
    /**
     * @var AssigneeResolver
     */
    private $resolver;

    public function __construct(AssigneeResolver $resolver)
    {
        $this->resolver = $resolver;
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
            $users = array_filter(
                $this->resolver->defaultUsers(),
                function ($username) use ($prefix) {
                    return stripos($username, $prefix) !== false;
                }
            );

            return !empty($users) ? array_values($users) : null;
        }

        return null;
    }

    /**
     * Get definition of a word.
     * Example: \b\w+\b. PCRE delimiters and options must not be provided.
     *
     * @return  string
     */
    public function getWordDefinition()
    {
        return '[\(\)a-zA-Z0-9. -]+';
    }
}
