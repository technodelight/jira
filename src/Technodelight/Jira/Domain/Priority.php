<?php

namespace Technodelight\Jira\Domain;

class Priority
{
    private $id;
    private $name;
    private $description;
    private $statusColor;

    public static function fromArray(array $status)
    {
        $instance = new self;
        $instance->id = $status['id'];
        $instance->name = $status['name'];
        $instance->description = isset($status['description']) ? $status['description'] : '';
        $instance->statusColor = isset($status['statusColor']) ? $status['statusColor'] : '';

        return $instance;
    }

    public static function createEmpty()
    {
        $instance = new self;
        $instance->id = '';
        $instance->name = '';
        $instance->description = '';
        $instance->statusColor = '';

        return $instance;
    }

    public function id()
    {
        return $this->id;
    }

    public function name()
    {
        return $this->name;
    }
    public function description()
    {
        return $this->description;
    }
    public function statusColor()
    {
        return $this->statusColor;
    }
    public function __toString()
    {
        return $this->name();
    }
    private function __construct()
    {
    }
}
