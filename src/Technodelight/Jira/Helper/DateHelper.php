<?php

namespace Technodelight\Jira\Helper;

use DateTime;

class DateHelper
{
    private $secondsMap = [
        'd' => 27000, // 7h 30m
        'h' => 3600,
        'm' => 60,
        's' => 1,
    ];

    public static function dateTimeFromJira($dateString)
    {
        $dateString = substr($dateString, 0, strpos($dateString, '.'))
            . substr($dateString, strpos($dateString, '+'));
        return DateTime::createFromFormat(DateTime::ISO8601, $dateString);
    }

    public function secondsToHuman($seconds)
    {
        if ($seconds === 0) {
            return 'none';
        }

        $human = [];
        foreach ($this->secondsMap as $stringRepresentation => $amount) {
            if ($seconds < 1) {
                break;
            }
            $value = floor($seconds / $amount);
            $seconds-= ($value * $amount);
            if ($value >= 1) {
                $human[] = sprintf('%d%s', $value, $stringRepresentation);
            }
        }
        return implode(' ', $human);
    }
}
