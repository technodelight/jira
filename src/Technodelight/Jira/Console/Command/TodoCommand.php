<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Template\IssueRenderer;

class TodoCommand extends AbstractCommand
{
    private $issueTypeFilter = [
        'bugs' => ['Defect', 'Bug'],
        'tasks' => ['Technical Sub-Task', 'Story'],
        'stories' => ['Story'],
    ];

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
            ->addOption(
                'bugs',
                'b',
                InputOption::VALUE_NONE,
                'show bugs only'
            )
            ->addOption(
                'tasks',
                't',
                InputOption::VALUE_NONE,
                'show tasks only'
            )
            ->addOption(
                'stories',
                's',
                InputOption::VALUE_NONE,
                'show stories only'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = $this->projectArgument($input);

        $issueFilter = [];
        foreach ($this->issueTypeFilter as $option => $types) {
            if ($input->getOption($option)) {
                $issueFilter = array_merge($issueFilter, $types);
            }
        }
        $issues = $this->getService('technodelight.jira.api')->todoIssues($project, $issueFilter);

        if (count($issues) == 0) {
            $output->writeln(sprintf('No tickets available to pick up on project %s.', $project));
            return;
        }

        $output->writeln(
            sprintf(
                'There are %d open %s in the open sprints' . PHP_EOL,
                count($issues),
                $this->getService('technodelight.jira.pluralize_helper')->pluralize('issue', count($issues))
            )
        );

        $renderer = $this->getService('technodelight.jira.issue_renderer');
        $renderer->setOutput($output);
        $renderer->renderIssues($issues);
    }
}
