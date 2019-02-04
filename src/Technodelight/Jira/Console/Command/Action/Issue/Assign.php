<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Input\Issue\Assignee\Assignee;
use Technodelight\Jira\Console\Input\Issue\Assignee\AssigneeResolver;

class Assign extends Command
{
    /**
     * @var Assignee
     */
    private $assigneeInput;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var IssueKeyResolver
     */
    private $issueKeyResolver;

    public function setAssigneeInput(Assignee $assignee)
    {
        $this->assigneeInput = $assignee;
    }

    public function setIssueKeyResolver(IssueKeyResolver $issueKeyResolver)
    {
        $this->issueKeyResolver = $issueKeyResolver;
    }

    public function setJiraApi(Api $api)
    {
        $this->api = $api;
    }

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
            ->addOption(
                'unassign',
                'u',
                InputOption::VALUE_NONE,
                'Unassign issue'
            )
            ->addOption(
                'default',
                null,
                InputOption::VALUE_NONE,
                'Assign issue to default assignee'
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->issueKeyResolver->argument($input, $output);
        if (!$input->getArgument('assignee') && !$input->getOption('unassign')) {
            $input->setArgument('assignee', $this->assigneeInput->userPicker($input, $output));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $assignee = $this->determineAssigneeFromInput($input);

        $this->api->assignIssue((string) $issueKey, $assignee);

        if ($assignee === AssigneeResolver::UNASSIGN) {
            $output->writeln(
                sprintf('<info>%s</info> was unassigned successfully', $issueKey)
            );
        } else {
            $output->writeln(
                sprintf(
                    '<info>%s</info> was assigned successfully to <comment>%s</comment>',
                    $issueKey,
                    $assignee === AssigneeResolver::DEFAULT_ASSIGNEE ? 'Default asignee' : $assignee
                )
            );
        }
    }

    /**
     * @param InputInterface $input
     * @return int|mixed|null
     */
    protected function determineAssigneeFromInput(InputInterface $input)
    {
        switch (true) {
            case $input->getOption('unassign'):
                return AssigneeResolver::UNASSIGN;
            case $input->getOption('default'):
                return AssigneeResolver::DEFAULT_ASSIGNEE;
            default:
                return $input->getArgument('assignee');
        }
    }
}
