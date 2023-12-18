<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Dashboard;

use DateTime;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Domain\User;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\DashboardCollection as Collection;

class WorklogFetcher
{
    public const MODE_DAILY = 1;
    public const MODE_WEEKLY = 2;
    public const MODE_MONTHLY = 3;

    public function __construct(
        private readonly Api $jira,
        private readonly WorklogHandler $worklogHandler
    ) {}

    public function fetch($dateString, User $user = null, $mode = self::MODE_DAILY): Collection
    {
        $from = $this->defineDate($dateString, $mode, true);
        $to = $this->defineDate($dateString, $mode, false);
        $logs = $this->worklogHandler->find($from, $to)->filterByUser($user ? $user : $this->jira->user());

        $issueKeys = $logs->issueKeys();
        if (!empty($issueKeys)) {
            $issues = $this->jira->retrieveIssues($issueKeys);
            foreach ($logs as $log) {
                if ($log->issueKey()) {
                    /** @var $log Worklog */
                    $log->assignIssue($issues->find($log->issueKey()));
                } elseif ($log->issueId()) {
                    $log->assignIssue($issues->findById($log->issueId()));
                }
            }
        }

        return Collection::fromWorklogCollection($logs, $from, $to);
    }

    private function defineDate(string $dateString, int $mode, bool $start): DateTime
    {
        switch ($mode) {
            default:
            case self::MODE_DAILY:
                return new DateTime($dateString);
            case self::MODE_WEEKLY:
                return new DateTime($this->defineWeekStr($dateString, $start ? 1 : 7));
            case self::MODE_MONTHLY:
                return new DateTime($this->defineMonthStr($dateString, $start));
        }
    }

    private function defineWeekStr(string $dateString, int $day): string
    {
        $dayOfWeek = date('N', strtotime($dateString));
        $operator = $day < $dayOfWeek ? '-' : '+';
        $delta = abs($dayOfWeek - $day);

        $date = date('Y-m-d', strtotime($dateString));

        return date(
            'Y-m-d',
            strtotime(sprintf('%s %s %s day', $date, $operator, $delta))
        );
    }

    private function defineMonthStr(string $dateString, bool $startOfMonthFlag): string
    {
        if ($startOfMonthFlag) {
            return date('Y-m-01', strtotime($dateString));
        }

        return date('Y-m-t', strtotime($dateString));
    }
}
