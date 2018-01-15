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

        $this->renderWeek($output, $collection);
    }

    private function renderWeek(OutputInterface $output, Collection $collection)
    {
        $rows = [];
        $headers = $this->tableHeaders($collection);

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
                if (!isset($rows['Sum'][$dayNo])) {
                    $rows['Sum'][$dayNo] = 0;
                }
                $rows['Sum'][$dayNo]+= $log->timeSpentSeconds();
            }

        }

        // sum logged / max seconds
        $sum = $rows['Sum'];
        unset($rows['Sum']);
        ksort($rows);

        $aDay = $this->dateHelper->humanToSeconds('1d');
        foreach ($sum as $date => $timeSpentSeconds) {
            if ($aDay == $timeSpentSeconds) {
                $sum[$date] = '1d';
            } else {
                $sum[$date] = $this->dateHelper->secondsToHuman($timeSpentSeconds);
            }
        }
        ksort($sum);
        array_unshift($sum, 'Total');
        $rows[] = new TableSeparator();
        $rows['Sum'] = $sum;

        // use the style for this table
        $table = new Table($output);
        $table
            ->setHeaders(array_values($headers))
            ->setRows(array_values($rows));
        $table->render();
    }

    private function tableHeaders(Collection $collection)
    {
        $headers = ['Issue'];
        foreach ($collection as $date => $logs) {
            $headers[$date->format('N')] = $date->format('l');
        }
        ksort($headers);

        return $headers;
    }

    private function shortenWorklogComment($text, $length = 15)
    {
        $wrapped = explode(PHP_EOL, wordwrap($text, $length));
        return array_shift($wrapped) . (count($wrapped) >= 1 ? '..' : '');
    }
}
