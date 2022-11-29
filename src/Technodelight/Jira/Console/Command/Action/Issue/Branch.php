<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Helper\CheckoutBranch;

class Branch extends Command
{
    public function __construct(
        private readonly Api $api,
        private readonly CheckoutBranch $checkoutBranch,
        private readonly IssueKeyResolver $issueKeyResolver
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('issue:branch')
            ->setDescription('Generate branch name using issue data')
            ->setAliases(['branch'])
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::OPTIONAL,
                'IssueKey to use for branch name generation'
            )
            ->addOption(
                'local',
                'l',
                InputOption::VALUE_NONE,
                'Choose an existing branch automagically'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $this->checkoutBranch->checkoutToBranch($input, $output, $this->api->retrieveIssue($issueKey));

        return self::SUCCESS;
    }
}
