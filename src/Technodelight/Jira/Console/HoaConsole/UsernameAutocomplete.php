<?php

namespace Technodelight\Jira\Console\HoaConsole;

use Hoa\Console\Readline\Autocompleter\Autocompleter;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\UserPickerResult;

class UsernameAutocomplete implements Autocompleter
{
    /**
     * @var \Technodelight\Jira\Domain\Issue
     */
    private $issue;

    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private $api;

    private $usernames;

    public function __construct(Issue $issue, Api $api)
    {
        $this->issue = $issue;
        $this->api = $api;
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
        $matches = $this->getMatchesForPrefix($this->issue, $prefix);
        $autocompletedValues = $this->getAutocompletedValues($matches);
        if (count($autocompletedValues) == 1) {
            return end($autocompletedValues);
        } elseif (count($autocompletedValues) > 1) {
            return $autocompletedValues;
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
        return '(\[~[^\b]+|@[^\b]+)';
    }

    private function getMatchesForPrefix(Issue $issue, $prefix)
    {
        $userPrefix = ltrim($prefix, '[~@]');
        $issueUsers = array_filter(
            $this->getUsersFromIssue($issue),
            function($username) use ($userPrefix) {
                if (empty($userPrefix)) {
                    return true;
                }
                return strpos($username, $userPrefix) !== false;
            }
        );
        $userPickerUsers = array_map(
            function(UserPickerResult $user) {
                return $user->name();
            },
            $this->api->userPicker($userPrefix)
        );
        return array_unique(array_merge($issueUsers, $userPickerUsers));
    }

    private function getUsersFromIssue(Issue $issue)
    {
        if (!isset($this->usernames)) {
            $this->usernames = [$issue->creatorUser()->key(), $issue->assigneeUser()->key()];
            foreach ($issue->comments() as $comment) {
                $this->usernames[] = $comment->author()->key();
            }
            $this->usernames = array_unique($this->usernames);
        }

        return $this->usernames;
    }

    private function getAutocompletedValues(array $matches)
    {
        return array_map(
            function($username) {
                return sprintf('[~%s]', $username);
            },
            $matches
        );
    }
}
