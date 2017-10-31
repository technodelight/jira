<?php

namespace Technodelight\Jira\Console\IssueStats;

class Event
{
    const UPDATE = 'update';
    const VIEW = 'view';

    private $eventType;
    private $time;

    private static $eventTypes = [
        self::UPDATE => 'Update',
        self::VIEW => 'View',
    ];

    public static function fromString($eventType)
    {
        if (!isset(self::$eventTypes[$eventType])) {
            throw new \InvalidArgumentException(
                sprintf('"%s" event type is invalid', $eventType)
            );
        }

        $instance = new self;
        $instance->eventType = $eventType;
        $instance->time = time();

        return $instance;
    }

    public static function fromArray(array $array)
    {
        $instance = new self;
        list ($instance->eventType, $instance->time) = $array;
        return $instance;
    }

    public function is($type)
    {
        return $type == $this->eventType;
    }

    public function type()
    {
        return $this->eventType;
    }

    public function time()
    {
        return $this->time;
    }

    public function asArray()
    {
        return [$this->eventType, $this->time];
    }

    public function __toString()
    {
        return (string) $this->eventType;
    }


    private function __construct()
    {
    }
}
