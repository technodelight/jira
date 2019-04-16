<?php

namespace Technodelight\Jira\Console\Input\Issue\Assignee;

use Symfony\Component\Console\Input\InputInterface;

class AssigneeResolver
{
    const UNASSIGN = null;
    const DEFAULT_ASSIGNEE = -1;

    private $defaultUsers = [
        '(Unassign)' => self::UNASSIGN,
        '(Default Assignee)' => self::DEFAULT_ASSIGNEE,
    ];

    public function resolve(InputInterface $input)
    {
        switch (true) {
            case $input->getOption('unassign'):
                return self::UNASSIGN;
            case $input->getOption('default'):
                return self::DEFAULT_ASSIGNEE;
            default:
                return $input->getArgument('assignee');
        }
    }

    public function defaultUsers()
    {
        return array_keys($this->defaultUsers);
    }

    public function isDefaultUser($username)
    {
        return array_key_exists($username, $this->defaultUsers);
    }

    public function fetchValueForDefaultUser($username)
    {
        if ($this->isDefaultUser($username)) {
            return $this->defaultUsers[$username];
        }

        return $username;
    }
}
