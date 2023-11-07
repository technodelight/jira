<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Input\Issue\Assignee;

use Symfony\Component\Console\Input\InputInterface;

class AssigneeResolver
{
    public const UNASSIGN = null;
    public const DEFAULT_ASSIGNEE = -1;

    private array $defaultUsers = [
        '(Unassign)' => self::UNASSIGN,
        '(Default Assignee)' => self::DEFAULT_ASSIGNEE,
    ];

    public function resolve(InputInterface $input): mixed
    {
        return match (true) {
            $input->getOption('unassign') => self::UNASSIGN,
            $input->getOption('default') => self::DEFAULT_ASSIGNEE,
            default => $input->getArgument('assignee'),
        };
    }

    public function defaultUsers(): array
    {
        return array_keys($this->defaultUsers);
    }

    public function isDefaultUser(string $username): bool
    {
        return array_key_exists($username, $this->defaultUsers);
    }

    public function fetchValueForDefaultUser(string $username): string
    {
        if ($this->isDefaultUser($username)) {
            return $this->defaultUsers[$username];
        }

        return $username;
    }
}
