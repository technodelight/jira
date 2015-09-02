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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = $this->getApplication()->config()->project();
        if ($input->getArgument('project')) {
            $project = $input->getArgument('project');
        }
        $issues = $this->getApplication()->jira()->inprogressIssues($project);
        $renderer = new SearchResultRenderer;

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
        $output->writeln($renderer->renderIssues($issues));
    }

    private function pluralizedIssue($count)
    {
        if ($count <= 1) {
            return 'issue';
        }

        return 'issues';
    }
}
