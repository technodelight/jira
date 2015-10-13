<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\Worklog;

class DashboardCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('dashboard')
            ->setDescription('Show your daily/weekly dashboard')
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Show your worklogs for the given date, could be "yesterday", "last week", "2015-09-28", today by default',
                'today'
            )
            ->addOption(
                'week',
                'w',
                InputOption::VALUE_NONE,
                'Display worklog for the week defined by date argument'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $input->getArgument('date');
        $from = $this->defineFrom($date, $input->getOption('week'));
        $to = $this->defineTo($date, $input->getOption('week'));
        $jira = $this->getApplication()->jira();
        $issues = $jira->retrieveIssuesHavingWorklogsForUser('"' . $from . '"', '"' . $to . '"');
        $user = $jira->user();

        if (count($issues) == 0) {
            $output->writeln("You don't have any issues at the moment, which has worklog in range");
            return;
        }

        $worklogs = $jira->retrieveIssuesWorklogs($this->issueKeys($issues));
        $logs = $this->filterLogsByDateAndUser($worklogs, $from, $to, $user['displayName']);

        $summary = 0;
        foreach ($logs as $log) {
            $summary+= $log->timeSpentSeconds();
        }

        $output->writeln(
            sprintf(
                'You have been working on %d %s %s' . PHP_EOL,
                count($issues),
                $this->pluralizedIssue(count($issues)),
                $from == $to ? "on $from" : "from $from to $to"
            )
        );

        if ($input->getOption('week')) {
            $this->renderWeek($output, $logs);
        } else {
            $this->renderDay($output, $logs);
        }

        $output->writeln(
            sprintf(
                'Total time logged: %s' . PHP_EOL,
                $this->getApplication()->dateHelper()->secondsToHuman($summary)
            )
        );
    }

    private function renderWeek(OutputInterface $output, array $logs)
    {
        $rows = [];
        $headers = [];
        foreach ($logs as $log) {
            $headers[$log->date()] = $log->date();
        }
        foreach ($logs as $log) {
            if (!isset($rows[$log->issueKey()])) {
                $rows[$log->issueKey()] = array_fill_keys($headers, '');
            }
            $rows[$log->issueKey()][$log->date()] = sprintf(
                '%s: %s' . PHP_EOL . '  %s',
                $log->issueKey(),
                $log->timeSpent(),
                $this->shortenWorklogComment($log->comment())
            );
        }

        ksort($rows);
        $table = $this->getHelper('table');
        $table
            ->setLayout(TableHelper::LAYOUT_COMPACT)
            ->setHeaders(array_values($headers))
            ->setRows($rows);
        $table->render($output);
    }

    private function renderDay(OutputInterface $output, array $logs)
    {
        $rows = array();
        foreach ($logs as $log) {
            $rows[] = array($log->issueKey(), $log->timeSpent(), $log->date());
        }
        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('Issue', 'Work log', 'Date'))
            ->setRows($this->orderByDate($rows));
        $table->render($output);
    }

    private function orderByDate(array $rows)
    {
        uasort($rows, function($a, $b) {
            if ($a[2] == $b[2]) {
                return 0;
            }

            return $a[2] < $b[2] ? -1 : 1;
        });

        return $rows;
    }

    private function filterLogsByDateAndUser(array $logs, $from, $to, $username)
    {
        return array_filter(
            $logs,
            function(Worklog $log) use ($from, $to, $username) {
                if ($log->author() != $username) {
                    return false;
                }
                if ($log->date() >= $from && $log->date() <= $to) {
                    return $log;
                }
            }
        );
    }

    private function defineFrom($date, $weekFlag)
    {
        if ($weekFlag) {
            $date = $this->defineWeekStr($date, 1);
        }
        return date(
            'Y-m-d',
            strtotime($date)
        );
    }

    private function defineTo($date, $weekFlag)
    {
        if ($weekFlag) {
            $date = $this->defineWeekStr($date, 5);
        }
        return date(
            'Y-m-d',
            strtotime($date)
        );
    }

    private function defineWeekStr($date, $day)
    {
        $dayOfWeek = date('N', strtotime($date));
        $operator = $day < $dayOfWeek ? '-' : '+';
        $delta = abs($dayOfWeek - $day);
        return sprintf('%s %s %s day', $date, $operator, $delta);
    }

    private function issueKeys($issues)
    {
        $issueKeys = [];
        foreach ($issues as $issue) {
            $issueKeys[] = $issue->issueKey();
        }
        return $issueKeys;
    }

    private function shortenWorklogComment($text)
    {
        $wrapped = explode(PHP_EOL, wordwrap($text, 15));
        return array_shift($wrapped);
    }

    private function pluralizedIssue($count)
    {
        if ($count <= 1) {
            return 'issue';
        }

        return 'issues';
    }
}
