<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Renderer\Issue\Renderer;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input);
        /** @var \Technodelight\Jira\Api\JiraRestApi\Api $jira */
        $jira = $this->getService('technodelight.jira.api');
        $issue = $jira->retrieveIssue($issueKey);
        /** @var \Technodelight\Jira\Connector\WorklogHandler $worklogHandler */
        $worklogHandler = $this->getService('technodelight.jira.worklog_handler');
        $worklogs = $worklogHandler->findByIssue($issue);
        $issue->assignWorklogs($worklogs);

        /** @var \Technodelight\Jira\Template\IssueRenderer $renderer */
        $renderer = $this->getService('technodelight.jira.issue_renderer');
        $renderer->render($output, $issue, true);
    }
}
