<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BrowseIssueCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('browse')
            ->setDescription('Open issue in browser')
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input, $output);
        try {
            $issue = $this->jiraApi()->retrieveIssue($issueKey);
            $output->writeln(
                sprintf('Opening <info>%s</info> in default browser...', $issueKey)
            );
            passthru(sprintf('open "%s"', $issue->url()));
        } catch (\Exception $exception) {
            $output->writeln(
                sprintf(
                    'Cannot open <info>%s</info> the browser, reason: %s',
                    $issueKey,
                    sprintf("(%s) %s", get_class($exception), $exception->getMessage())
                )
            );
        }
    }

    /**
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }
}
