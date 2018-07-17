<?php

namespace Technodelight\Jira\Domain\Issue;

class IssueType
{
    private $name;
    private $description;

    public static function fromArray(array $issueType)
    {
        $instance = new self;
        $instance->name = $issueType['name'];
        $instance->description = $issueType['description'];
        return $instance;
    }

    public static function createEmpty()
    {
        $instance = new self;
        $instance->name = '';
        $instance->description = '';
        return $instance;
    }

    public function name()
    {
        return $this->name;
    }

    public function description()
    {
        return $this->description;
    }

    public function __toString()
    {
        return $this->name;
    }

    private function __construct()
    {
    }
}
