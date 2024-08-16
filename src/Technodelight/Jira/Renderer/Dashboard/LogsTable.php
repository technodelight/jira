<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Dashboard;

use DateTime;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\DateHelper;
use Technodelight\Jira\Domain\DashboardCollection;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\JiraTagConverter\Components\PrettyTable;
use Technodelight\Jira\Renderer\DashboardRenderer;

class LogsTable implements DashboardRenderer
{
    public function __construct(private readonly DateHelper $dateHelper)
    {
    }

    public function render(OutputInterface $output, DashboardCollection $collection): void
    {
        if (!$collection->count()) {
            return;
        }

        $weeklyCollections = $collection->splitToWeeks();
        foreach ($weeklyCollections as $weeklyCollection) {
            $this->renderWeek($output, $weeklyCollection, count($weeklyCollections));
        }
    }

    private function renderWeek(OutputInterface $output, DashboardCollection $collection, $weekCount): void
    {
        $dailySum = array_fill_keys($this->createDaysArray($collection), 0);
        $headers = $this->tableHeaders($collection, array_keys($dailySum));

        $rows = [];
        foreach ($collection as $date => $logs) {
            $dayNo = $date->format('N');

            foreach ($logs as $log) {
                /** @var $log Worklog */
                $key = (string) $log->issueIdentifier();
                if (!isset($rows[$key])) {
                    $rows[$key] = array_fill_keys(array_keys($headers), '');
                    $rows[$key][0] = $log->issueKey();
                }
                if (!isset($rows[$key][$dayNo])) {
                    $rows[$key][$dayNo] = '';
                }
                $rows[$key][$dayNo].= sprintf(
                    PHP_EOL . '%s %s',
                    $this->dateHelper->secondsToHuman($log->timeSpentSeconds() ?? 0),
                    $this->shortenWorklogComment($log->comment() ?? '')
                );
                $rows[$key][$dayNo] = trim($rows[$key][$dayNo]);
                $dailySum[$dayNo]+= $log->timeSpentSeconds();
            }
        }

        $this->tableFooter($rows, $dailySum);

        // use the style for this table
        $table = new PrettyTable($output);
        $table
            ->setHeaders(array_values($headers))
            ->setRows(array_values($rows));
        if ($weekCount > 1) {
            $output->writeln($this->tableDateSpanHeader($collection));
        }
        $table->render();
    }

    private function tableHeaders(DashboardCollection $collection, array $days): array
    {
        $headers = ['Issue'];

        foreach ($collection->fromToDateRange(false) as $date) {
            if (in_array($date->format('N'), $days)) {
                $headers[$date->format('N')] = $date->format('l');
            }
        }
        ksort($headers);

        return $headers;
    }

    private function tableFooter(array &$rows, array $dailySum): void
    {
        $rows[] = new TableSeparator();
        $summary = ['Total'];
        foreach ($dailySum as $logSeconds) {
            $summary[] = $this->dateHelper->secondsToHuman($logSeconds);
        }
        $rows[] = $summary;
    }

    private function shortenWorklogComment(string $text, int $length = 20): string
    {
        $wrapped = explode(PHP_EOL, wordwrap($text, $length));
        return array_shift($wrapped) . (count($wrapped) >= 1 ? '..' : '');
    }

    private function tableDateSpanHeader(DashboardCollection $collection): string
    {
        return sprintf(
            'From %s to %s:',
            $collection->from()->format('Y-m-d l'),
            $collection->to()->format('Y-m-d l')
        );
    }

    private function createDaysArray(DashboardCollection $collection): array
    {
        $days = array_filter(array_map(function(DateTime $date) use ($collection) {
            static $weekends = [6, 7];

            $day = $date->format('N');
            if (in_array($day, $weekends)) {
                return ($collection->findMatchingLogsForDate($date)->count() > 0) ? $day : null;
            }

            return $day;
        }, $collection->fromToDateRange(false)));
        sort($days);

        return $days;
    }
}
