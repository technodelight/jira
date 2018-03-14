<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\OpenApp\OpenApp;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Domain\Issue;

class Browse extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('show:browse')
            ->setDescription('Open issue in browser')
            ->setAliases(['browse'])
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123)'
            )
            ->addOption(
                'pr',
                'r',
                InputOption::VALUE_NONE,
                'Open GitHub PR link instead of JIRA'
            )
            ->addOption(
                'ci',
                'c',
                InputOption::VALUE_NONE,
                'Open CI link (from GitHub PR data) instead of JIRA'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input, $output);
        try {
            $issue = $this->jiraApi()->retrieveIssue($issueKey);
            if ($input->getOption('pr')) {
                $this->openPr($output, $issue);
            } elseif ($input->getOption('ci')) {
                $this->openCi($output, $issue);
            } else {
                $this->openIssueLink($output, $issue);
            }
        } catch (\Exception $exception) {
            $output->writeln(
                sprintf(
                    'Cannot open <info>%s</info> in browser, reason: %s',
                    $issueKey,
                    sprintf("(%s) %s", get_class($exception), $exception->getMessage())
                )
            );
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Technodelight\Jira\Domain\Issue $issue
     */
    private function openIssueLink(OutputInterface $output, Issue $issue)
    {
        $output->writeln(
            sprintf('Opening <info>%s</info> in browser...', $issue->issueKey())
        );
        $this->openApp()->open($issue->url());
    }

    private function openPr(OutputInterface $output, Issue $issue)
    {
        $issues = $this->listOpenGitHubIssues($issue);
        $hubIssue = array_shift($issues);
        if ($hubIssue && empty($issues)) {
            $output->writeln(
                sprintf(
                    'Opening first open pull request <comment>#%d</comment> for issue <comment>%s</comment>...',
                    $hubIssue['number'],
                    $issue->ticketNumber()
                )
            );
            $this->openApp()->open($hubIssue['html_url']);
        } else {
            $output->writeln(
                sprintf('<error>Cannot find any open PR for issue %s</error>', $issue->ticketNumber())
            );
        }
    }

    private function openCi(OutputInterface $output, Issue $issue)
    {
        $issues = $this->listOpenGitHubIssues($issue);
        $hubIssue = array_shift($issues);
        if ($hubIssue && empty($issues)) {
            $commits = $this->gitHub()->prCommits($hubIssue['number']);
            $last = end($commits);
            $combined = $this->gitHub()->statusCombined($last['sha']);
            $status = array_shift($combined['statuses']);

            $output->writeln(
                sprintf(
                    'Opening %s link for first open pull request <comment>#%d</comment>...',
                    $status['context'],
                    $hubIssue['number']
                )
            );
            $this->openApp()->open($status['target_url']);
        }
    }

    /**
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @return array
     */
    private function listOpenGitHubIssues(Issue $issue)
    {
        return array_filter(
            $this->gitHub()->issues('open'),
            function ($hubIssue) use ($issue) {
                return strpos($hubIssue['title'], $issue->issueKey()) === 0;
            }
        );
    }

    /**
     * @return OpenApp
     */
    private function openApp()
    {
        return $this->getService('technodelight.jira.console.open');
    }

    /**
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }

    /**
     * @return \Technodelight\Jira\Helper\HubHelper
     */
    private function gitHub()
    {
        return $this->getService('technodelight.jira.hub_helper');
    }
}
