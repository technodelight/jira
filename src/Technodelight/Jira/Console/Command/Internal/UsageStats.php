<?php


namespace Technodelight\Jira\Console\Command\Internal;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Command\AbstractCommand;

class UsageStats extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('internal:stats')
            ->setDescription('Show statistics about your issue usage')
            ->addOption(
                'clear',
                'c',
                InputOption::VALUE_NONE,
                'Clear stats'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('clear')) {
            $this->clearStats($output);
        } else {
            $this->displayStats($output);
        }
    }

    private function clearStats(OutputInterface $output)
    {
        $this->issueStats()->clear();
        $output->writeln('Stats has been cleared');
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    private function displayStats(OutputInterface $output)
    {
        $stats = $this->statCollector()->all();
        $stats->orderByMostRecent();
        $output->writeln(sprintf('You interacted with <info>%d</info> issues', count($stats)));
        foreach ($stats as $issueKey => $stat) {
            if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET) {
                $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
                $output->writeln($issueKey);
                $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
            } else {
                $output->writeln(
                    sprintf(
                        '<info>%s</info> <comment>total:</> %d <comment>view:</> %d <comment>update:</> %d',
                        $issueKey,
                        $stat['total'],
                        $stat['view'],
                        $stat['update']
                    )
                );
            }
        }
    }
    
    /**
     * @return \Technodelight\Jira\Console\IssueStats\StatCollector
     */
    private function statCollector()
    {
        return $this->getService('technodelight.jira.console.issue_stats.stat_collector');
    }

    /**
     * @return \Technodelight\Jira\Console\IssueStats\IssueStats
     */
    private function issueStats()
    {
        return $this->getService('technodelight.jira.console.issue_stats');
    }
}
