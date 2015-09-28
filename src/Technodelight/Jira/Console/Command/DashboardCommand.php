<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Command\Command;
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

        $rows = array();
        $summary = 0;
        foreach ($issues as $issue) {
            $logs = $this->filterLogsByDateAndUser($jira->retrieveIssueWorklogs($issue->issueKey()), $from, $to, $user['displayName']);
            foreach ($logs as $log) {
                $summary+= $log->timeSpentSeconds();
                $rows[] = array($issue->issueKey(), $log->timeSpent(), $log->date());
            }
        }

        $output->writeln(
            sprintf(
                'You have been working on %d %s %s' . PHP_EOL,
                count($issues),
                $this->pluralizedIssue(count($issues)),
                $from == $to ? "on $from" : "from $from to $to"
            )
        );

        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('Issue', 'Work log', 'Date'))
            ->setRows($this->orderByDate($rows));

        $table->render($output);
        $output->writeln(
            sprintf(
                'Total time logged: %s' . PHP_EOL,
                $this->getApplication()->dateHelper()->secondsToHuman($summary)
            )
        );
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
        return date('Y-m-d', strtotime($date . ($weekFlag ? ' monday' : '')));
    }

    private function defineTo($date, $weekFlag)
    {
        return date('Y-m-d', strtotime($date . ($weekFlag ? ' friday' : '')));
    }

    private function pluralizedIssue($count)
    {
        if ($count <= 1) {
            return 'issue';
        }

        return 'issues';
    }
}
