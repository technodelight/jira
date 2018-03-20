<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyOrWorklogIdResolver;

class Version extends Command
{
    /**
     * @var Api
     */
    private $jira;

    protected function configure()
    {
        $this
            ->setName('issue:version')
            ->setAliases(['version'])
            ->setDescription('Set version and fixVersion attributes for an issue')
            ->addArgument(
                IssueKeyOrWorklogIdResolver::NAME,
                InputArgument::OPTIONAL,
                'Issue key, like PROJ-123 OR a specific worklog\'s ID'
            )
            ->addOption(
                'fix-version',
                null,
                InputOption::VALUE_REQUIRED,
                'Set fixVersion number'
            )
            ->addOption(
                'version',
                null,
                InputOption::VALUE_REQUIRED,
                'Set version number'
            )
            ->addOption(
                'remove',
                null,
                InputOption::VALUE_NONE,
                'Indicate if <version> should be removed'
            )
        ;
    }

    public function setJiraApi(Api $jira)
    {
        $this->jira = $jira;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //@TODO
    }
}
