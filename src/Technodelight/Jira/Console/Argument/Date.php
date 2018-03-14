<?php

namespace Technodelight\Jira\Console\Argument;

class Date
{
    const INVALID_DATE_STRING = 'Invalid date string: "%s"';
    private $date;

    public static function fromString($string)
    {
        $date = new self;
        if (false === strtotime($string)) {
            throw new \InvalidArgumentException(
                sprintf(self::INVALID_DATE_STRING, $string)
            );
        }
        $date->date = $string;

        return $date;
    }

    public function toDateTime()
    {
        return new \DateTime($this->date);
    }

    public function __toString()
    {
        return $this->date;
    }

    private function __construct()
    {
    }
}
