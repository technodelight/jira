<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        if (count($issues) == 0) {
            $output->writeln("You don't have any issues at the moment, which has worklog in range");
            return;
        }

        $output->writeln(
            sprintf(
                'You have been working on %d %s from %s to %s' . PHP_EOL,
                count($issues),
                $this->pluralizedIssue(count($issues)),
                $from,
                $to
            )
        );

        $rows = array();
        foreach ($issues as $issue) {
            $logs = $jira->retrieveIssueWorklogs($issue->issueKey());
            foreach ($logs['worklogs'] as $log) {
                $rows[] = array($issue->issueKey(), $log['timeSpent'], $log['started']);
            }
        }

        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('Issue', 'Work log', 'Date'))
            ->setRows($rows);

        $table->render($output);
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
