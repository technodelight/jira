<?php

namespace Technodelight\Jira\Console\Dashboard;

use DateTime;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Domain\Worklog;

class Dashboard
{
    const MODE_DAILY = 1;
    const MODE_WEEKLY = 2;
    const MODE_MONTHLY = 3;

    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private $jira;
    /**
     * @var \Technodelight\Jira\Connector\WorklogHandler
     */
    private $worklogHandler;

    public function __construct(Api $jira, WorklogHandler $worklogHandler)
    {
        $this->jira = $jira;
        $this->worklogHandler = $worklogHandler;
    }

    public function fetch($dateString, $mode = self::MODE_DAILY)
    {
        $from = $this->defineDate($dateString, $mode, true);
        $to = $this->defineDate($dateString, $mode, false);
        $logs = $this->worklogHandler->find($from, $to);

        $issueKeys = $logs->issueKeys();
        if ($issueKeys) {
            $issues = $this->jira->retrieveIssues($issueKeys);
            foreach ($logs as $log) {
                /** @var $log Worklog */
                $log->assignIssue($issues->find($log->issueKey()));
            }
        }

        return Collection::fromWorklogCollection($logs, $from, $to);
    }

    private function defineDate($dateString, $mode, $start)
    {
        switch ($mode) {
            case self::MODE_DAILY:
                return new DateTime($dateString);
            case self::MODE_WEEKLY:
                return new DateTime($this->defineWeekStr($dateString, $start ? 1 : 5));
            case self::MODE_MONTHLY:
                return new DateTime($this->defineMonthStr($dateString, $start ? true : false));
        }
    }

    private function defineWeekStr($dateString, $day)
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

    private function defineMonthStr($dateString, $startOfMonthFlag)
    {
        if ($startOfMonthFlag) {
            return date('Y-m-01', strtotime($dateString));
        } else {
            return date('Y-m-t', strtotime($dateString));
        }
    }
}
