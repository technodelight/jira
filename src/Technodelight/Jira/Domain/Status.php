<?php

namespace Technodelight\Jira\Domain;

class Status
{
    private $statusCategoryColor;
    private $statusCategory;
    private $id;
    private $name;
    private $description;

    public static function fromArray(array $status)
    {
        $instance = new self;
        $instance->description = $status['description'];
        $instance->name = $status['name'];
        $instance->id = $status['id'];
        $instance->statusCategory = $status['statusCategory']['name'];
        $instance->statusCategoryColor = $status['statusCategory']['colorName'];

        return $instance;
    }

    public static function createEmpty()
    {
        $instance = new self;
        $instance->description = '';
        $instance->name = '';
        $instance->id = '';
        $instance->statusCategory = '';
        $instance->statusCategoryColor = '';

        return $instance;
    }

    public function description()
    {
        return $this->description;
    }

    public function name()
    {
        return $this->name;
    }
    public function id()
    {
        return $this->id;
    }
    public function statusCategory()
    {
        return $this->statusCategory;
    }
    public function statusCategoryColor()
    {
        return $this->statusCategoryColor;
    }
    public function __toString()
    {
        return $this->name();
    }
    private function __construct()
    {
    }
}
