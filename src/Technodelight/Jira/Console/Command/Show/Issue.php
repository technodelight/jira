<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Api\JiraRestApi\SearchQuery\Builder;
use Technodelight\Jira\Connector\GitShell\Api as Git;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Command\IssueRendererAware;
use Technodelight\Jira\Console\IssueStats\StatCollector;
use Technodelight\Jira\Domain\Issue as DomainIssue;
use Technodelight\Jira\Helper\Wordwrap;
use Technodelight\Jira\Template\IssueRenderer;

class Issue extends Command implements IssueRendererAware
{
    public function __construct(
        private readonly Api $api,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly IssueRenderer $issueRenderer,
        private readonly WorklogHandler $worklogHandler,
        private readonly Wordwrap $wordwrap,
        private readonly IssueKeyResolver\Guesser $guesser,
        private readonly Git $git,
        private readonly StatCollector $statCollector
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('show')
            ->setDescription('Show an issue')
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123), defaults to current issue, taken from branch name',
                null,
                function (CompletionInput $completionInput) {
                    return array_filter(array_unique(array_merge([
                        array_map(
                            function (DomainIssue $issue) use ($completionInput) {
                                $issueKey = $issue->issueKey()->issueKey();
                                if (str_starts_with($issueKey, $completionInput->getCompletionValue())) {
                                    return $issueKey;
                                }
                            },
                            iterator_to_array($this->api->search(
                                sprintf(
                                    'issueKey ~ "%s" and assignee was currentUser()',
                                    addslashes($completionInput->getCompletionValue())
                                )
                            ))
                        ),
                        $this->guesser->guessIssueKey(
                            $completionInput->getCompletionValue(), $this->git->currentBranch()
                        ),
                        array_filter(
                            $this->statCollector->all()->orderByMostRecent()->issueKeys(),
                            static function (string $issueKey) use ($completionInput) {
                                return str_starts_with($issueKey, $completionInput->getCompletionValue());
                            }
                        )
                    ])));
                }
            )
        ;
        $this->getDefinition()->getArgument('issueKey')->hasCompletion();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $issue = $this->api->retrieveIssue($issueKey);
        $parentOf = $this->api->search(
            Builder::factory()->parent($issueKey)->assemble()
        );
        foreach ($parentOf as $subtask) {
            $issue->subtasks()->add($subtask);
        }

        $this->tryFetchAndAssignWorklogs($output, $issue);

        $this->issueRenderer->render($output, $issue, $input->getOptions());

        return self::SUCCESS;
    }

    private function tryFetchAndAssignWorklogs(OutputInterface $output, DomainIssue $issue): void
    {
        $formatterHelper = new FormatterHelper;
        try {
            $worklogs = $this->worklogHandler->findByIssue($issue);
            $issue->assignWorklogs($worklogs);
        } catch (\Exception $e) {
            $output->writeln(
                $formatterHelper->formatBlock([
                    'Sorry, cannot display worklogs right now...',
                    $this->wordwrap->wrap(join(' ', explode(PHP_EOL, $e->getMessage())))]
                , 'error', true)
            );
        }
    }
}
