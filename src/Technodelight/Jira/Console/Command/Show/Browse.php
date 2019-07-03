<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Technodelight\CliOpen\CliOpen as OpenApp;
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
                $this->openPr($input, $output, $issue);
            } elseif ($input->getOption('ci')) {
                $this->openCi($input, $output, $issue);
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
        $this->renderHeader($output, $issue, false);
        $output->writeln(
            sprintf('Opening jira for <info>%s</info> in browser...', $issue->issueKey())
        );
        $this->openApp()->open($issue->url());
    }

    private function openPr(InputInterface $input, OutputInterface $output, Issue $issue)
    {
        $issues = $this->listGitHubIssues($issue, 'open');
        $hubIssue = array_shift($issues);
        if ($hubIssue && empty($issues)) {
            $this->openHubIssue($output, $issue, $hubIssue);
            return;
        }

        if (!empty($issues)) {
            $selectedIdx = $this->questionHelper()->ask($input, $output, new ChoiceQuestion(
                'Please select a Pull Request',
                array_map(function (array $hubIssue) {
                    return $hubIssue['number'] . ' ' . $hubIssue['name'];
                }, $issues)
            ));
            $this->openHubIssue($output, $issue, $issues[$selectedIdx]);
        } else {
            $issues = $this->listGitHubIssues($issue, 'all');
            if (empty($issues)) {
                // choose from open PRs OR from closed PRs
                $output->writeln(
                    sprintf('<error>Cannot find any PRs for issue %s</error>', $issue->ticketNumber())
                );
                return;
            }
            $selectedIdx = $this->questionHelper()->ask($input, $output, new ChoiceQuestion(
                'Please select a Pull Request',
                array_map(function (array $hubIssue) {
                    return $hubIssue['number'] . ' ' . $hubIssue['name'];
                }, $issues)
            ));
            $this->openHubIssue($output, $issue, $issues[$selectedIdx]);
        }
    }

    private function openCi(InputInterface $input, OutputInterface $output, Issue $issue)
    {
        $issues = $this->listGitHubIssues($issue, 'open');
        $hubIssue = array_shift($issues);
        if ($hubIssue && empty($issues)) {
            $commits = $this->gitHub()->prCommits($hubIssue['number']);
            $last = end($commits);
            $combined = $this->gitHub()->statusCombined($last['sha']);
            $this->renderHeader($output, $issue);
            if (count($combined['statuses']) == 0) {
                $output->writeln(
                    sprintf(
                        'No CI link for the <info>%s</info> issue\'s first open pull request <comment>#%d</comment>...',
                        $issue->key(),
                        $hubIssue['number']
                    )
                );
                return;
            }
            $status = $this->selectCiStatus($input, $output, $combined['statuses']);

            $output->writeln(
                sprintf(
                    'Opening <info>%s</info> link for first open pull request <comment>#%d</comment>...',
                    $status['context'],
                    $hubIssue['number']
                )
            );
            $this->openApp()->open($status['target_url']);
        }
    }

    /**
     * @param array $combined
     * @return array
     */
    private function selectCiStatus(InputInterface $input, OutputInterface $output, array $statuses)
    {
        if (count($statuses) > 1) {
            $opts = array_map(function(array $status) { return $status['context']; }, $statuses);
            $q = new ChoiceQuestion('Select CI provider', $opts);
            $idx = $this->questionHelper()->ask($input, $output, $q);
            return $statuses[$idx];
        }
        return reset($statuses);
    }

    /**
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @param string $state
     * @return array
     */
    private function listGitHubIssues(Issue $issue, $state)
    {
        return array_filter(
            $this->gitHub()->issues($state),
            function ($hubIssue) use ($issue) {
                return strpos($hubIssue['title'], (string) $issue->issueKey()) === 0;
            }
        );
    }

    private function renderHeader(OutputInterface $output, Issue $issue, $withPr = true)
    {
        $this->issueHeaderRenderer()->render($output, $issue);
        if ($withPr) {
            $this->gitHubIssueRenderer()->render($output, $issue);
        }
        $output->writeln('');
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @param array $hubIssue
     */
    private function openHubIssue(OutputInterface $output, Issue $issue, array $hubIssue)
    {
        $this->renderHeader($output, $issue);
        $output->writeln(
            sprintf(
                'Opening first open pull request <info>#%d</info> for issue <comment>%s</comment>...',
                $hubIssue['number'],
                $issue->ticketNumber()
            )
        );
        $this->openApp()->open($hubIssue['html_url']);
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

    /**
     * @return \Symfony\Component\Console\Helper\QuestionHelper
     */
    private function questionHelper()
    {
        return $this->getService('console.question_helper');
    }

    /**
     * @return \Technodelight\Jira\Renderer\Issue\GitHub
     */
    private function gitHubIssueRenderer()
    {
        return $this->getService('technodelight.jira.renderer.issue.github');
    }

    /**
     * @return \Technodelight\Jira\Renderer\Issue\Header
     */
    private function issueHeaderRenderer()
    {
        return $this->getService('technodelight.jira.renderer.issue.header');
    }
}
