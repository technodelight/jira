<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;

class Edit extends Command
{
    /**
     * @var Api
     */
    private $jira;

    public function setJiraApi(Api $jira)
    {
        $this->jira = $jira;
    }

    protected function configure()
    {
        $this
            ->setName('issue:edit')
            ->setDescription('Edit issue')
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::OPTIONAL,
                'IssueKey to edit'
            )
            ->addArgument(
                'field',
                InputArgument::OPTIONAL,
                'Field name to edit'
            )
            ->addArgument(
                'value',
                InputArgument::OPTIONAL,
                'Field value to set'
            )
            ->addOption(
                'no-notifiy',
                'n',
                InputOption::VALUE_NONE,
                'Skip notifying watchers about the change'
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        //@TODO implementation missing
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //@TODO implementation missing
    }
}
