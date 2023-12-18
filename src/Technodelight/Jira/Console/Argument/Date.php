<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Argument;

use DateTime;
use InvalidArgumentException;

class Date
{
    private const INVALID_DATE_STRING = 'Invalid date string: "%s"';
    private string $date;

    public static function fromString($string): self
    {
        $date = new self;
        if (false === strtotime($string)) {
            throw new InvalidArgumentException(
                sprintf(self::INVALID_DATE_STRING, $string)
            );
        }
        $date->date = $string;

        return $date;
    }

    public function toDateTime(): DateTime
    {
        return new DateTime($this->date);
    }

    public function __toString(): string
    {
        return $this->date;
    }

    private function __construct()
    {
        // only constructed statically
    }
}
