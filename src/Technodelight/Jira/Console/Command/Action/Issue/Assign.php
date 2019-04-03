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
use Technodelight\Jira\Renderer\Action\Issue\Assign\Error;
use Technodelight\Jira\Renderer\Action\Issue\Assign\Success;
use Technodelight\Jira\Renderer\Action\Renderer;

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
     * @var AssigneeResolver
     */
    private $assigneeResolver;
    /**
     * @var IssueKeyResolver
     */
    private $issueKeyResolver;
    /**
     * @var Renderer
     */
    private $resultRenderer;

    public function __construct(Api $api, Assignee $assigneeInput, AssigneeResolver $assigneeResolver, IssueKeyResolver $issueKeyResolver, Renderer $resultRenderer)
    {
        $this->api = $api;
        $this->assigneeInput = $assigneeInput;
        $this->assigneeResolver = $assigneeResolver;
        $this->issueKeyResolver = $issueKeyResolver;
        $this->resultRenderer = $resultRenderer;

        parent::__construct();
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
        $assignee = $this->assigneeResolver->resolve($input);

        try {
            $this->api->assignIssue($issueKey, $assignee);

            return $this->resultRenderer->render(
                $output,
                Success::fromIssueKeyAndAssignee($issueKey, $this->assigneeName($assignee))
            );
        } catch (\Exception $e) {
            return $this->resultRenderer->render(
                $output,
                Error::fromExceptionIssueKeyAndAssignee($e, $issueKey, $this->assigneeName($assignee))
            );
        }
    }

    /**
     * @param $assignee
     * @return |null
     */
    protected function assigneeName($assignee)
    {
        if ($assignee === AssigneeResolver::UNASSIGN) {
            return null;
        }

        return $assignee === AssigneeResolver::DEFAULT_ASSIGNEE ? 'Default assignee' : $assignee;
    }
}
