<?php

namespace Technodelight\Jira\Console\Command;

use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\Api;
use Technodelight\Jira\Console\Command\AbstractCommand;

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
        $command = new IssueFilterCommand($this->container, 'run', $input->getArgument('jql') ?: null);
        $command->execute($input, $output);
    }
}
