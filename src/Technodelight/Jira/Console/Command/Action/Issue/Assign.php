<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Hoa\Console\Readline\Readline;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Console\HoaConsole\UserPickerAutocomplete;

class Assign extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('issue:assign')
            ->setDescription('Change issue assignee')
            ->setAliases(['assign'])
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::OPTIONAL,
                'Issue Key where the assignee has to be changed. Can guess from current feature branch'
            )
            ->addArgument(
                'assignee',
                InputArgument::OPTIONAL,
                'Assignee username'
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('assignee')) {
            $input->setArgument('assignee', $this->userPicker($output));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input, $output);
        $assignee = $input->getArgument('assignee');

        $this->jiraApi()->assignIssue((string) $issueKey, $assignee);
        $output->writeln(
            sprintf('<info>%s</info> was assigned successfully to <info>%s</info>', $issueKey, $assignee)
        );
    }

    private function userPicker(OutputInterface $output)
    {
        $readline = new Readline;
        $readline->setAutocompleter(
            new UserPickerAutocomplete($this->jiraApi())
        );
        $output->write('<comment>Please provide a username for assignee:</comment> ');
        return $readline->readLine();
    }

    /**
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }

    /**
     * @return \Technodelight\Jira\Console\Argument\InteractiveIssueSelector
     */
    private function issueSelector()
    {
        return $this->getService('technodelight.jira.console.interactive_issue_selector');
    }
}