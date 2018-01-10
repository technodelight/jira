<?php

namespace Technodelight\Jira\Domain\Issue;

class IssueType
{
    private $name;

    public static function fromArray(array $issueType)
    {
        $instance = new self;
        $instance->name = $issueType['name'];
        return $instance;
    }

    public static function createEmpty()
    {
        $instance = new self;
        $instance->name = '';
        return $instance;
    }

    public function name()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->name;
    }

    private function __construct()
    {
    }
}
