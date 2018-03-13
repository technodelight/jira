<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Console\Dashboard\Dashboard as ConsoleDashboard;

class Dashboard extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('show:dashboard')
            ->setDescription('Show your daily/weekly dashboard')
            ->setAliases(['dashboard'])
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
            ->addOption(
                'month',
                'm',
                InputOption::VALUE_NONE,
                'Display your monthly worklog'
            )
            ->addOption(
                'list',
                'l',
                InputOption::VALUE_NONE,
                'Render dashboard as a list'
            )
            ->addOption(
                'summary',
                's',
                InputOption::VALUE_NONE,
                'Render summary only'
            )
            ->addOption(
                'table',
                't',
                InputOption::VALUE_NONE,
                'Render dashboard as table'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $this->dateArgument($input);
        $mode = $this->mode($input);
        $collection = $this->dashboardConsole()->fetch($date, $mode);
        $this->renderer($mode, $input)->render($output, $collection);
    }

    private function mode(InputInterface $input)
    {
        if ($input->getOption('week')) {
            return ConsoleDashboard::MODE_WEEKLY;
        } elseif ($input->getOption('month')) {
            return ConsoleDashboard::MODE_MONTHLY;
        }
        return ConsoleDashboard::MODE_DAILY;
    }

    /**
     * @return \Technodelight\Jira\Console\Dashboard\Dashboard
     */
    private function dashboardConsole()
    {
        return $this->getService('technodelight.jira.console.dashboard.dashboard');
    }

    /**
     * @return \Technodelight\Jira\Renderer\DashboardRenderer
     */
    private function renderer($mode, InputInterface $input)
    {
        if ($input->getOption('summary')) {
            return $this->getService('technodelight.jira.renderer.dashboard.summary');
        }
        if ($input->getOption('list')) {
            return $this->getService('technodelight.jira.renderer.dashboard.list');
        }
        if ($input->getOption('table')) {
            return $this->getService('technodelight.jira.renderer.dashboard.table');
        }

        if ($mode == ConsoleDashboard::MODE_MONTHLY) {
            return $this->getService('technodelight.jira.renderer.dashboard.summary');
        }
        if ($mode == ConsoleDashboard::MODE_WEEKLY) {
            return $this->getService('technodelight.jira.renderer.dashboard.table');
        }
        return $this->getService('technodelight.jira.renderer.dashboard.list');
    }
}
