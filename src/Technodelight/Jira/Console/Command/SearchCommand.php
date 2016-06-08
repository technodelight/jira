<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Api\Api;

class SearchCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('search')
            ->setDescription('Search in Jira using JQL')
            ->addArgument(
                'jql',
                InputArgument::REQUIRED,
                'The JQL query'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jql = $input->getArgument('jql');
        /** @var Technodelight\Jira\Api\Api $jira */
        $jira = $this->getService('technodelight.jira.api');
        /** @var Technodelight\Template\IssueRenderer $renderer */
        $renderer = $this->getService('technodelight.jira.issue_renderer');
        $renderer->setOutput($output);
        $renderer->renderIssues($jira->search($jql, Api::FIELDS_ALL));
    }
}
