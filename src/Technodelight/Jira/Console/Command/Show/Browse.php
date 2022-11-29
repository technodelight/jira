<?php

namespace Technodelight\Jira\Console\Command\Show;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\CliOpen\CliOpen as OpenApp;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Renderer\Issue\Header;

class Browse extends Command
{
    public function __construct(
        private readonly OpenApp $openApp,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly Api $jira,
        private readonly Header $headerRenderer
    ) {
        parent::__construct();
    }

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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        try {
            $issue = $this->jira->retrieveIssue($issueKey);
            $this->openIssueLink($output, $issue);

            return self::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln(
                sprintf(
                    'Cannot open <info>%s</info> in browser, reason: %s',
                    $issueKey,
                    sprintf("(%s) %s", get_class($exception), $exception->getMessage())
                )
            );

            return self::FAILURE;
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
        $this->openApp->open($issue->url());
    }

//    private function openPr(InputInterface $input, OutputInterface $output, Issue $issue)
//    {
//        $issues = $this->listGitHubIssues($issue, 'open');
//        $hubIssue = array_shift($issues);
//        if ($hubIssue && empty($issues)) {
//            $this->openHubIssue($output, $issue, $hubIssue);
//
//            return;
//        }
//
//        if (!empty($issues)) {
//            $selectedIdx = $this->questionHelper()->ask(
//                $input,
//                $output,
//                new ChoiceQuestion(
//                    'Please select a Pull Request',
//                    array_map(
//                        function (array $hubIssue) {
//                            return $hubIssue['number'] . ' ' . $hubIssue['name'];
//                        },
//                        $issues
//                    )
//                )
//            );
//            $this->openHubIssue($output, $issue, $issues[$selectedIdx]);
//        } else {
//            $issues = $this->listGitHubIssues($issue, 'all');
//            if (empty($issues)) {
//                // choose from open PRs OR from closed PRs
//                $output->writeln(
//                    sprintf('<error>Cannot find any PRs for issue %s</error>', $issue->ticketNumber())
//                );
//
//                return;
//            }
//            $selectedIdx = $this->questionHelper()->ask(
//                $input,
//                $output,
//                new ChoiceQuestion(
//                    'Please select a Pull Request',
//                    array_map(
//                        function (array $hubIssue) {
//                            return $hubIssue['number'] . ' ' . $hubIssue['name'];
//                        },
//                        $issues
//                    )
//                )
//            );
//            $this->openHubIssue($output, $issue, $issues[$selectedIdx]);
//        }
//    }

//    private function openCi(InputInterface $input, OutputInterface $output, Issue $issue)
//    {
//        $issues = $this->listGitHubIssues($issue, 'open');
//        $hubIssue = array_shift($issues);
//        if ($hubIssue && empty($issues)) {
//            $commits = $this->gitHub()->prCommits($hubIssue['number']);
//            $last = end($commits);
//            $combined = $this->gitHub()->statusCombined($last['sha']);
//            $this->renderHeader($output, $issue);
//            if (count($combined['statuses']) == 0) {
//                $output->writeln(
//                    sprintf(
//                        'No CI link for the <info>%s</info> issue\'s first open pull request <comment>#%d</comment>...',
//                        $issue->key(),
//                        $hubIssue['number']
//                    )
//                );
//
//                return;
//            }
//            $status = $this->selectCiStatus($input, $output, $combined['statuses']);
//
//            $output->writeln(
//                sprintf(
//                    'Opening <info>%s</info> link for first open pull request <comment>#%d</comment>...',
//                    $status['context'],
//                    $hubIssue['number']
//                )
//            );
//            $this->openApp()->open($status['target_url']);
//        }
//    }

//    /**
//     * @param array $combined
//     * @return array
//     */
//    private function selectCiStatus(InputInterface $input, OutputInterface $output, array $statuses)
//    {
//        if (count($statuses) > 1) {
//            $opts = array_map(
//                function (array $status) {
//                    return $status['context'];
//                },
//                $statuses
//            );
//            $q = new ChoiceQuestion('Select CI provider', $opts);
//            $idx = $this->questionHelper()->ask($input, $output, $q);
//
//            return $statuses[$idx];
//        }
//
//        return reset($statuses);
//    }

//    /**
//     * @param \Technodelight\Jira\Domain\Issue $issue
//     * @param string $state
//     * @return array
//     */
//    private function listGitHubIssues(Issue $issue, $state)
//    {
//        return array_filter(
//            $this->gitHub()->issues($state),
//            function ($hubIssue) use ($issue) {
//                return strpos($hubIssue['title'], (string)$issue->issueKey()) === 0;
//            }
//        );
//    }

    private function renderHeader(OutputInterface $output, Issue $issue, $withPr = false)
    {
        $this->headerRenderer->render($output, $issue);
//        if ($withPr) {
//            $this->gitHubIssueRenderer()->render($output, $issue);
//        }
        $output->writeln('');
    }

//    /**
//     * @param \Symfony\Component\Console\Output\OutputInterface $output
//     * @param \Technodelight\Jira\Domain\Issue $issue
//     * @param array $hubIssue
//     */
//    private function openHubIssue(OutputInterface $output, Issue $issue, array $hubIssue)
//    {
//        $this->renderHeader($output, $issue);
//        $output->writeln(
//            sprintf(
//                'Opening first open pull request <info>#%d</info> for issue <comment>%s</comment>...',
//                $hubIssue['number'],
//                $issue->ticketNumber()
//            )
//        );
//        $this->openApp()->open($hubIssue['html_url']);
//    }
}
