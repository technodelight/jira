<?php

namespace Technodelight\Jira\Helper;

class DateHelper
{
    private $jiraFormat = 'Y-m-dTH:i:s.000+0100';
    private $secondsMap = [
        'd' => 27000, // 7h 30m
        'h' => 3600,
        'm' => 60,
        's' => 1,
    ];

    public static function dateTimeFromJira($dateString)
    {
        return \DateTime::createFromFormat($dateString, $this->jiraFormat);
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
