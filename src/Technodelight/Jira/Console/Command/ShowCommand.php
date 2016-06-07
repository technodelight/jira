<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Command\AbstractCommand;

class ShowCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('show')
            ->setDescription('Show an issue')
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123), defaults to current issue, taken from branch name'
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $git = $this->getService('technodelight.jira.git_helper');
        if (!$this->issueKeyArgument($input)) {
            $issueKey = $git->issueKeyFromCurrentBranch();
            if (empty($issueKey)) {
                throw new \InvalidArgumentException('Cannot retrieve issue key from current branch');
            }
            $input->setArgument('issueKey', $issueKey);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input);
        $jira = $this->getService('technodelight.jira.api');
        $issue = $jira->retrieveIssue($issueKey);

        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $renderer = $this->getService('technodelight.jira.issue_renderer');
        $renderer->setOutput($output);
        $renderer->render($issue);
    }

    private function retrieveWorklogs($issues, $limit)
    {
        return $this->getService('technodelight.jira.api')->retrieveIssuesWorklogs(
            $this->issueKeys($issues), $limit
        );
    }

    private function issueKeys($issues)
    {
        return array_map(
            function($issue) {
                return $issue->issueKey();
            },
            $issues
        );
    }
}
