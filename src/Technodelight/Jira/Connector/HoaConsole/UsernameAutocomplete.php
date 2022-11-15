<?php

namespace Technodelight\Jira\Connector\HoaConsole;

use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\UserPickerResult;

class UsernameAutocomplete implements Autocompleter
{
    private Issue $issue;
    private Api $api;

    private array $usernamesCache;

    public function __construct(Issue $issue, Api $api)
    {
        $this->issue = $issue;
        $this->api = $api;
    }

    public function complete(string $prefix): ?array
    {
        return $this->getAutocompletedValues($this->getMatchesForPrefix($this->issue, $prefix));
    }

    public function getWordDefinition(): string
    {
        return '\[~[^]]+|@[^]]+';
    }

    private function getMatchesForPrefix(Issue $issue, $prefix): array
    {
        $userPrefix = ltrim($prefix, '[~@]');
        $issueUsers = array_filter(
            $this->getUsersFromIssue($issue),
            static function ($username) use ($userPrefix) {
                if (empty($userPrefix)) {
                    return true;
                }
                return str_contains($username, $userPrefix);
            }
        );
        $userPickerUsers = array_map(
            static function (UserPickerResult $user) {
                return $user->name();
            },
            $this->api->userPicker($userPrefix)
        );
        return array_unique(array_merge($issueUsers, $userPickerUsers));
    }

    private function getUsersFromIssue(Issue $issue): array
    {
        if (!isset($this->usernamesCache)) {
            $this->usernamesCache = [$issue->creatorUser()?->key(), $issue->assigneeUser()?->key()];
            foreach ($issue->comments() as $comment) {
                $this->usernamesCache[] = $comment->author()->key();
            }
            $this->usernamesCache = array_unique($this->usernamesCache);
        }

        return $this->usernamesCache;
    }

    private function getAutocompletedValues(array $matches): array
    {
        return array_map(
            static function ($username) {
                return sprintf('[~%s]', $username);
            },
            $matches
        );
    }
}
