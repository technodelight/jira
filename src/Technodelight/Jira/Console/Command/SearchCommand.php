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
        try {
            $jql = $input->getArgument('jql');
            /** @var Technodelight\Jira\Api\Api $jira */
            $jira = $this->getService('technodelight.jira.api');
            $issueCollection = $jira->search($jql, Api::FIELDS_ALL);
            if (!count($issueCollection)) {
                throw new \RuntimeException(
                    sprintf('<error>No issues matching your query</> "%s"', $jql)
                );
            }

            /** @var Technodelight\Template\IssueRenderer $renderer */
            $renderer = $this->getService('technodelight.jira.issue_renderer');
            $renderer->setOutput($output);
            $renderer->renderIssues($issueCollection);

        } catch(\Exception $exception) {
            if ($exception instanceof ClientException) {
                throw new \InvalidArgumentException(
                    sprintf('<error>There is an error in your query</> "%s"', $jql)
                );
            }
            throw $exception;
        }
    }
}
