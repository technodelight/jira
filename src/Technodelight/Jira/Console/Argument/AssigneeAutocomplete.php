<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Argument;

use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\UserPickerResult;

class AssigneeAutocomplete
{
    public function __construct(
        private readonly Api $api
    ) {
    }

    public function autocomplete(string $buffer): array
    {
        return array_map(fn(UserPickerResult $user) => $user->displayName(), $this->api->userPicker($buffer, 10));
    }
}
