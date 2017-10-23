<?php

namespace Technodelight\Jira\Domain;

class UserPickerResult
{
    private $key;
    private $name;
    private $displayName;
    private $html;

    public static function fromArray(array $result)
    {
        $instance = new self;
        $instance->key = $result['key'];
        $instance->name = $result['name'];
        $instance->displayName = $result['displayName'];
        $instance->html = $result['html'];
        return $instance;
    }

    public function key()
    {
        return $this->key;
    }

    public function name()
    {
        return $this->name;
    }

    public function displayName()
    {
        return $this->displayName;
    }

    public function html()
    {
        return $this->html;
    }

    private function __construct()
    {
    }
}
