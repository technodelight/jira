<?php

declare(strict_types=1);

namespace Technodelight\Jira\Helper;

use Technodelight\Jira\Api\JiraRestApi\Api;

class AccountIdUsernameReplacer
{
    public function __construct(private readonly Api $api)
    {
    }

    public function replace(string $body): string
    {
        preg_match_all('~accountid:[a-f0-9]+~', $body, $matches);
        $accountIds = [];
        foreach ($matches[0] ?? [] as $match) {
            [,$accountId] = explode(':', $match) + ['', ''];
            if ($accountId) {
                $accountIds[] = $accountId;
            }
        }
        $users = $this->api->users($accountIds);
        foreach ($users as $user) {
            $body = str_replace('accountid:'.$user->id(), $user->displayName(), $body);
        }

        return $body;
    }
}
