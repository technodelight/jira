<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\AssigneeAutocomplete;
use Technodelight\Jira\Console\Argument\IssueKeyAutocomplete;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Input\Issue\Assignee\Assignee;
use Technodelight\Jira\Console\Input\Issue\Assignee\AssigneeResolver;
use Technodelight\Jira\Renderer\Action\Issue\Assign\Error;
use Technodelight\Jira\Renderer\Action\Issue\Assign\Success;
use Technodelight\Jira\Renderer\Action\Renderer;

class Assign extends Command
{
    public function __construct(
        private readonly Api $api,
        private readonly Assignee $assigneeInput,
        private readonly AssigneeResolver $assigneeResolver,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly Renderer $resultRenderer,
        private readonly IssueKeyAutocomplete $issueKeyAutocomplete,
        private readonly AssigneeAutocomplete $assigneeAutocomplete
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('issue:assign')
            ->setDescription('Change issue assignee')
            ->setAliases(['assign'])
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::OPTIONAL,
                'Issue Key where the assignee has to be changed. Can guess from current feature branch',
                null,
                fn(CompletionInput $completionInput)
                    => $this->issueKeyAutocomplete->autocomplete($completionInput->getCompletionValue())
            )
            ->addArgument(
                'assignee',
                InputArgument::OPTIONAL,
                'Assignee username',
                null,
                fn(CompletionInput $completionInput)
                    => $this->assigneeAutocomplete->autocomplete($completionInput->getCompletionValue())
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
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->issueKeyResolver->argument($input, $output);
        if (!$input->getArgument('assignee') && !$input->getOption('unassign')) {
            $input->setArgument('assignee', $this->assigneeInput->userPicker($input, $output));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $assignee = $this->assigneeResolver->resolve($input);

        try {
            $this->api->assignIssue($issueKey, $assignee);

            return $this->resultRenderer->render(
                $output,
                Success::fromIssueKeyAndAssignee($issueKey, $this->assigneeName($assignee))
            );
        } catch (Exception $e) {
            return $this->resultRenderer->render(
                $output,
                Error::fromExceptionIssueKeyAndAssignee($e, $issueKey, $this->assigneeName($assignee))
            );
        }
    }

    private function assigneeName($assignee): ?string
    {
        if ($assignee === AssigneeResolver::UNASSIGN) {
            return null;
        }

        return $assignee === AssigneeResolver::DEFAULT_ASSIGNEE ? 'Default assignee' : $assignee;
    }
}
