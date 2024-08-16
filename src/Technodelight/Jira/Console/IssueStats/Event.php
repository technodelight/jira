<?php

namespace Technodelight\Jira\Console\IssueStats;

use InvalidArgumentException;

class Event
{
    public const UPDATE = 'update';
    public const VIEW = 'view';

    private string $eventType;
    private int $time;

    private static array $eventTypes = [
        self::UPDATE => 'Update',
        self::VIEW => 'View',
    ];

    public static function fromString($eventType): Event
    {
        if (!isset(self::$eventTypes[$eventType])) {
            throw new InvalidArgumentException(
                sprintf('"%s" event type is invalid', $eventType)
            );
        }

        $instance = new self;
        $instance->eventType = $eventType;
        $instance->time = time();

        return $instance;
    }

    public static function fromArray(array $array): Event
    {
        $instance = new self;
        list ($instance->eventType, $instance->time) = $array;
        return $instance;
    }

    public function isType($type): bool
    {
        return $type === $this->eventType;
    }

    public function type(): string
    {
        return $this->eventType;
    }

    public function time(): int
    {
        return $this->time;
    }

    public function asArray(): array
    {
        return [$this->eventType, $this->time];
    }

    public function __toString(): string
    {
        return $this->eventType;
    }


    private function __construct()
    {
    }
}
