<?php

namespace Technodelight\Jira\Api\GitShell;

class Branch
{
    private $name;
    private $remote;
    private $current;

    public static function fromArray(array $params)
    {
        $entry = new self;
        $entry->name = $params['name'];
        $entry->remote = $params['remote'];
        $entry->current = $params['current'];
        return $entry;
    }
    public function name()
    {
        return $this->name;
    }

    public function remote()
    {
        return $this->remote;
    }

    public function isRemote()
    {
        return !empty($this->remote);
    }

    public function current()
    {
        return (bool) $this->current;
    }

    public function __toString()
    {
        return sprintf(
            '%s %s',
            $this->name,
            $this->remote ? '(remote)' : '(local)'
        );
    }
}
