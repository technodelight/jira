<?php

namespace Technodelight\Jira\Renderer\Dashboard;

use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\DateHelper;
use Technodelight\Jira\Api\JiraTagConverter\Components\PrettyTable;
use Technodelight\Jira\Console\Dashboard\Collection;
use Technodelight\Jira\Renderer\DashboardRenderer;

class LogsTable implements DashboardRenderer
{
    /**
     * @var DateHelper
     */
    private $dateHelper;

    public function __construct(DateHelper $dateHelper)
    {
        $this->dateHelper = $dateHelper;
    }

    /**
     * @param OutputInterface $output
     * @param Collection $collection
     * @throws \Exception
     */
    public function render(OutputInterface $output, Collection $collection)
    {
        if (!$collection->count()) {
            return;
        }

        $weeklyCollections = $collection->splitToWeeks();
        foreach ($weeklyCollections as $weeklyCollection) {
            $this->renderWeek($output, $weeklyCollection, count($weeklyCollections));
        }
    }

    private function renderWeek(OutputInterface $output, Collection $collection, $weekCount)
    {
        $dailySum = array_fill_keys($this->createDaysArray($collection), 0);
        $headers = $this->tableHeaders($collection, array_keys($dailySum));

        foreach ($collection as $date => $logs) {
            $dayNo = $date->format('N');

            foreach ($logs as $log) {
                /** @var $log \Technodelight\Jira\Domain\Worklog */
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
                    $this->dateHelper->secondsToHuman($log->timeSpentSeconds()),
                    $this->shortenWorklogComment($log->comment())
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

    private function tableHeaders(Collection $collection, array $days)
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

    private function tableFooter(array &$rows, $dailySum)
    {
        $rows[] = new TableSeparator();
        $summary = ['Total'];
        foreach ($dailySum as $day => $logSeconds) {
            $summary[] = $this->dateHelper->secondsToHuman($logSeconds);
        }
        $rows[] = $summary;
    }

    private function shortenWorklogComment($text, $length = 20)
    {
        $wrapped = explode(PHP_EOL, wordwrap($text, $length));
        return array_shift($wrapped) . (count($wrapped) >= 1 ? '..' : '');
    }

    private function tableDateSpanHeader(Collection $collection)
    {
        return sprintf(
            'From %s to %s:',
            $collection->from()->format('Y-m-d l'),
            $collection->to()->format('Y-m-d l')
        );
    }

    private function createDaysArray(Collection $collection)
    {
        $weekends = [6,7];
        $days = array_filter(array_map(function(\DateTime $date) use ($weekends, $collection) {
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
