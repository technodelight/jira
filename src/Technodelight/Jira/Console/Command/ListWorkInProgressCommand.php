<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Technodelight\Jira\Api\JiraRestApi\SearchQuery\Builder;
use Technodelight\Jira\Domain\Project;
use Technodelight\Jira\Domain\Status;
use Technodelight\Jira\Template\IssueRenderer;

class ListWorkInProgressCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('in-progress')
            ->setDescription('List tickets picked up by you')
            ->addArgument(
                'projectKey',
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
        if ($input->getOption('all')) {
            $projects = $this->jiraApi()->projects(10);
            $choices = array_map(
                function(Project $project) {
                    return sprintf('<info>%s</info> %s', $project->key(), $project->name());
                },
                $projects
            );
            $choice = $this->questionHelper()->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    '<comment>Choose a project to list members process:</>',
                    array_map(
                        function(Project $project) {
                            return sprintf('<info>%s</info> %s', $project->key(), $project->name());
                        },
                        $projects
                    ),
                    0
                )
            );
            $projectKey = $projects[array_search($choice, $choices)]->key();
            $input->setArgument('projectKey', $projectKey);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectKey = $this->projectKeyResolver()->argument($input);
        $query = Builder::factory()
            ->statusCategory('In Progress');

        if (!empty($projectKey)) {
            $query->project($projectKey);
        }

        if (!$input->getOption('all')) {
            $query->assignee($this->jiraApi()->user()->key());
        }
        $issues = $this->jiraApi()->search($query->assemble());

        if (count($issues) == 0) {
            $output->writeln('You don\'t have any in-progress issues currently.');
            return 0;
        }

        $output->writeln(
            sprintf(
                'You have %d in progress %s%s' . PHP_EOL,
                count($issues),
                $this->getService('technodelight.jira.pluralize_helper')->pluralize('issue', count($issues)),
                $input->getOption('all') ? (sprintf(' on project <info>%s</info>', $input->getArgument('projectKey'))) : ''
            )
        );

        /** @var IssueRenderer $renderer */
        $renderer = $this->getService('technodelight.jira.issue_renderer');
        $renderer->renderIssues($output, $issues);

        return 0;
    }

    /**
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }

    /**
     * @return \Symfony\Component\Console\Helper\QuestionHelper
     */
    private function questionHelper()
    {
        return $this->getService('console.question_helper');
    }

    /**
     * @return \Technodelight\Jira\Console\Argument\ProjectKeyResolver
     */
    private function projectKeyResolver()
    {
        return $this->getService('technodelight.jira.console.argument.project_key_resolver');
    }
}
