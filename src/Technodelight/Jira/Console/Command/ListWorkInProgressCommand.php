<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Template\IssueRenderer;

class ListWorkInProgressCommand extends AbstractCommand
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
        /** @var DialogHelper $dialog */
        $dialog = $this->getService('console.dialog_helper');

        // ensure we display every information for in progress issues
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE && !$input->getOption('all')) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        if ($input->getOption('all') && !$input->getArgument('project')) {
            $projects = $this->getService('technodelight.jira.api')->projects(10);
            $index = $dialog->select(
                $output,
                PHP_EOL . '<comment>Choose a project to list members process:</>',
                array_map(
                    function(array $project) {
                        return sprintf('<info>%s</info> %s', $project['key'], $project['name']);
                    },
                    $projects
                ),
                0
            );
            $project = $projects[$index]['key'];
            $input->setArgument('project', $project);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issues = $this->getService('technodelight.jira.api')->inprogressIssues(
            $input->getArgument('project'),
            $input->getOption('all')
        );

        if (count($issues) == 0) {
            $output->writeln('You don\'t have any in-progress issues currently.');
            return 0;
        }

        $output->writeln(
            sprintf(
                'You have %d in progress %s%s' . PHP_EOL,
                count($issues),
                $this->getService('technodelight.jira.pluralize_helper')->pluralize('issue', count($issues)),
                $input->getOption('all') ? (sprintf(' on project <info>%s</info>', $input->getArgument('project'))) : ''
            )
        );

        /** @var IssueRenderer $renderer */
        $renderer = $this->getService('technodelight.jira.issue_renderer');
        $renderer->setOutput($output);
        $renderer->renderIssues($issues);

        return 0;
    }
}
