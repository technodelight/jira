<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Console\Command\IssueRendererAware;
use Technodelight\Jira\Domain\Issue as DomainIssue;

class Issue extends AbstractCommand implements IssueRendererAware
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
        $issueKey = $this->issueKeyArgument($input, $output);
        /** @var \Technodelight\Jira\Api\JiraRestApi\Api $jira */
        $jira = $this->getService('technodelight.jira.api');
        $issue = $jira->retrieveIssue($issueKey);

        $this->tryFetchAndAssignWorklogs($output, $issue);

        /** @var \Technodelight\Jira\Template\IssueRenderer $renderer */
        $renderer = $this->getService('technodelight.jira.issue_renderer');
        $renderer->render($output, $issue, $input->getOptions());
    }

    /**
     * @param OutputInterface $output
     * @param DomainIssue $issue
     */
    private function tryFetchAndAssignWorklogs(OutputInterface $output, DomainIssue $issue)
    {
        try {
            /** @var \Technodelight\Jira\Connector\WorklogHandler $worklogHandler */
            $worklogHandler = $this->getService('technodelight.jira.worklog_handler');
            $worklogs = $worklogHandler->findByIssue($issue);
            $issue->assignWorklogs($worklogs);
        } catch (\Exception $e) {
            $output->writeln(
                $this->formatterHelper()->formatBlock([
                    'Sorry, cannot display worklogs right now...',
                    $this->wordWrap()->wrap(join(' ', explode(PHP_EOL, $e->getMessage())))]
                , 'error', true)
            );
        }
    }

    /**
     * @return \Symfony\Component\Console\Helper\FormatterHelper
     */
    private function formatterHelper()
    {
        return $this->container->get('console.formatter_helper');
    }

    /**
     * @return \Technodelight\Jira\Helper\Wordwrap
     */
    private function wordWrap()
    {
        return $this->container->get('technodelight.jira.word_wrap');
    }
}
