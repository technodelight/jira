<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Technodelight\Jira\Template\SearchResultRenderer;

class ListWorkInProgressCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('in-progress')
            ->setDescription('List tickets picked up by you')
            ->addArgument(
                'project',
                InputArgument::OPTIONAL,
                'Project name if differing from repo configuration'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Shows other team member\'s progress'
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // ensure we display every information for in progress issues
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = $this->getApplication()->config()->project();
        if ($input->getArgument('project')) {
            $project = $input->getArgument('project');
        }
        $jira = $this->getApplication()->jira();
        $issues = $jira->inprogressIssues($project, $input->getOption('all'));
        $renderer = new SearchResultRenderer($output, $this->getHelper('formatter'));
        $worklogs = $jira->retrieveIssuesWorklogs(
            $this->issueKeys($issues),
            $output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG ? null : 10 // limit worklogs to render
        );

        if (count($issues) == 0) {
            $output->writeln('You don\'t have any in-progress issues currently.');
            return;
        }

        $output->writeln(
            sprintf(
                'You have %d in progress %s' . PHP_EOL,
                count($issues),
                $this->pluralizedIssue(count($issues))
            )
        );

        $renderer->addWorklogs($worklogs);
        $renderer->renderIssues($issues);
    }

    private function issueKeys($issues)
    {
        $issueKeys = [];
        foreach ($issues as $issue) {
            $issueKeys[] = $issue->issueKey();
        }
        return $issueKeys;
    }

    private function pluralizedIssue($count)
    {
        if ($count <= 1) {
            return 'issue';
        }

        return 'issues';
    }
}
