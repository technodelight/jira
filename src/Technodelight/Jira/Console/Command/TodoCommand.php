<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Technodelight\Jira\Template\SearchResultRenderer;

class TodoCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('todo')
            ->setDescription('List "Open" tickets')
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

        $issues = $this->getApplication()->jira()->todoIssues($project);
        $renderer = new SearchResultRenderer;

        if (count($issues) == 0) {
            $output->writeln(sprintf('No tickets available to pick up on project %s.', $project));
            return;
        }

        $output->writeln(sprintf('There are %d open issues in the open sprints' . PHP_EOL, count($issues)));
        $output->writeln($renderer->renderIssues($issues));
    }

}
