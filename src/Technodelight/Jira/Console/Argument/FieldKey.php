<?php

namespace Technodelight\Jira\Console\Argument;

class FieldKey
{
    private $issueKey;
    private $fieldKey;

    public static function fromIssueKeyAndFieldKey($issueKey, $fieldKey)
    {
        $instance = new self;
        $instance->issueKey = $issueKey;
        $instance->fieldKey = $fieldKey;
        return $instance;
    }

    public function issueKey()
    {
        return $this->issueKey;
    }

    public function fieldKey()
    {
        return $this->fieldKey;
    }
}
