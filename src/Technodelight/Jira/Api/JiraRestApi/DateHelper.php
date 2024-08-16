<?php

declare(strict_types=1);

namespace Technodelight\Jira\Api\JiraRestApi;

use DateTime;
use DateTimeZone;
use RuntimeException;
use Technodelight\SecondsToNone;
use Technodelight\SecondsToNone\Config;

class DateHelper
{
    public const FORMAT_FROM_JIRA = DateTime::ISO8601;
    public const FORMAT_TO_JIRA = 'Y-m-d\TH:i:s.000O';

    public function __construct(private readonly Config $config) {}

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    public static function dateTimeFromJira($dateString): ?DateTime
    {
        list(,$timeZone) = explode('+', $dateString, 2);
        $dateString = substr($dateString, 0, strpos($dateString, '.'))
            . substr($dateString, strpos($dateString, '+'));
        return DateTime::createFromFormat(self::FORMAT_FROM_JIRA, $dateString, new DateTimeZone('+' . $timeZone))
            ?: null;
    }

    public static function dateTimeToJira(string|DateTime $datetime): string
    {
        $date = ($datetime instanceof DateTime) ? $datetime : new DateTime($datetime);
        if ($date->format('H:i:s') == '00:00:00') {
            $date->setTime(12, 0, 0);
        }
        return $date->format(self::FORMAT_TO_JIRA);
    }

    public function secondsToHuman(int $seconds): string
    {
        return $this->getSTN()->secondsToHuman($seconds);
    }

    public function humanToSeconds(string $def): float|int
    {
        return $this->getSTN()->humanToSeconds($def);
    }

    public function stringToFormattedDate(string $dateString, string $format): string
    {
        return date($format, strtotime($dateString));
    }

    private function getSTN(): SecondsToNone
    {
        if (!class_exists('Technodelight\SecondsToNone')) {
            throw new RuntimeException('Technodelight\SecondsToNone class cannot be found!');
        }
        return new SecondsToNone($this->config);
    }
}
