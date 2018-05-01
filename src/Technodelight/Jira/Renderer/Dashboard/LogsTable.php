<?php

namespace Technodelight\Jira\Renderer\Dashboard;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Dashboard\Collection;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Renderer\DashboardRenderer;

class LogsTable implements DashboardRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\DateHelper
     */
    private $dateHelper;

    public function __construct(DateHelper $dateHelper)
    {
        $this->dateHelper = $dateHelper;
    }

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
        $headers = $this->tableHeaders($collection);
        $dailySum = array_fill_keys($this->createDaysArray($collection), 0);

        foreach ($collection as $date => $logs) {
            $dayNo = $date->format('N');

            foreach ($logs as $log) {
                /** @var $log \Technodelight\Jira\Domain\Worklog */
                if (!isset($rows[$log->issueKey()])) {
                    $rows[$log->issueKey()] = array_fill_keys(array_keys($headers), '');
                    $rows[$log->issueKey()][0] = $log->issueKey();
                }
                if (!isset($rows[$log->issueKey()][$dayNo])) {
                    $rows[$log->issueKey()][$dayNo] = '';
                }
                $rows[$log->issueKey()][$dayNo].= sprintf(
                    PHP_EOL . '%s %s',
                    $this->dateHelper->secondsToHuman($log->timeSpentSeconds()),
                    $this->shortenWorklogComment($log->comment())
                );
                $rows[$log->issueKey()][$dayNo] = trim($rows[$log->issueKey()][$dayNo]);
                $dailySum[$dayNo]+= $log->timeSpentSeconds();
            }
        }

        $this->tableFooter($rows, $dailySum);

        // use the style for this table
        $table = new Table($output);
        $table
            ->setHeaders(array_values($headers))
            ->setRows(array_values($rows));
        if ($weekCount > 1) {
            $output->writeln($this->tableDateSpanHeader($collection));
        }
        $table->render();
    }

    private function tableHeaders(Collection $collection)
    {
        $headers = ['Issue'];
        foreach ($collection->fromToDateRange(true) as $date) {
            $headers[$date->format('N')] = $date->format('l');
        }

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
        return array_map(function(\DateTime $date) {
            return $date->format('N');
        }, $collection->fromToDateRange(true));
    }
}
