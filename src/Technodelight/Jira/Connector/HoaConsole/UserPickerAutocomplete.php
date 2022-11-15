<?php

namespace Technodelight\Jira\Connector\HoaConsole;

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

    public function complete($prefix): ?array
    {
        if (!empty($prefix)) {
            $users = array_map(
                function(UserPickerResult $user) {
                    return $user->name();
                },
                $this->jira->userPicker($prefix)
            );

            return !empty($users) ? $users : null;
        }

        return null;
    }

    public function getWordDefinition(): string
    {
        return '[a-zA-Z0-9. -]+';
    }
}
