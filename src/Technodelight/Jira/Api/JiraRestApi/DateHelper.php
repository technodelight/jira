<?php

namespace Technodelight\Jira\Api\JiraRestApi;

use DateTime;
use DateTimeZone;
use Technodelight\SecondsToNone;
use Technodelight\SecondsToNone\Config;

class DateHelper
{
    const FORMAT_FROM_JIRA = DateTime::ISO8601;
    const FORMAT_TO_JIRA = 'Y-m-d\TH:i:s.000O';
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public static function dateTimeFromJira($dateString)
    {
        list(,$timeZone) = explode('+', $dateString, 2);
        $dateString = substr($dateString, 0, strpos($dateString, '.'))
            . substr($dateString, strpos($dateString, '+'));
        return DateTime::createFromFormat(self::FORMAT_FROM_JIRA, $dateString, new DateTimeZone('+' . $timeZone));
    }

    public static function dateTimeToJira($datetime)
    {
        $date = ($datetime instanceof DateTime) ? $datetime : new DateTime($datetime);
        if ($date->format('H:i:s') == '00:00:00') {
            $date->setTime(12, 0, 0);
        }
        return $date->format(self::FORMAT_TO_JIRA);
    }

    public function secondsToHuman($seconds)
    {
        return $this->getSTN()->secondsToHuman($seconds);
    }

    public function humanToSeconds($def)
    {
        return $this->getSTN()->humanToSeconds($def);
    }

    public function stringToFormattedDate($dateString, $format)
    {
        return date($format, strtotime($dateString));
    }

    private function getSTN()
    {
        if (!class_exists('Technodelight\SecondsToNone')) {
            throw new \RuntimeException('Technodelight\SecondsToNone class cannot be found!');
        }
        return new SecondsToNone($this->config);
    }
}
