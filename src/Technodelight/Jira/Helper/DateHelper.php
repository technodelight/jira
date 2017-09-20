<?php

namespace Technodelight\Jira\Helper;

use DateTime;
use Technodelight\SecondsToNone;
use Technodelight\SecondsToNone\Config;

class DateHelper
{
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
        return $this->getSTN()->secondsToHuman($seconds);
    }

    public function humanToSeconds($def)
    {
        return $this->getSTN()->humanToSeconds($def);
    }

    private function getSTN()
    {
        return new SecondsToNone($this->config);
    }
}
