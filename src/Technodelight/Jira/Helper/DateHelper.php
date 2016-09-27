<?php

namespace Technodelight\Jira\Helper;

use DateTime;

class DateHelper
{
    const ZERO_SECONDS = 'none';

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

    public static function dateTimeToJira($datetime)
    {
        $date = ($datetime instanceof \DateTime) ? $datetime : new \DateTime($datetime);
        if ($date->format('H:i:s') == '00:00:00') {
            $date->setTime(12, 0, 0);
        }
        return $date->format('Y-m-d\TH:i:s.000O');
    }

    public function secondsToHuman($seconds)
    {
        if ($seconds === 0) {
            return self::ZERO_SECONDS;
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

    public function humanToSeconds($def)
    {
        if ($def == self::ZERO_SECONDS) {
            return 0;
        }

        $parts = explode(' ', $def);
        $seconds = 0;
        foreach ($parts as $part) {
            $part = trim($part);
            $unit = substr($part, -1);
            $number = substr($part, 0, -1);
            $seconds+= isset($this->secondsMap[$unit]) ? ($number * $this->secondsMap[$unit]) : 0;
        }
        return $seconds;
    }
}
