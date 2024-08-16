<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Technodelight\GitShell\ApiInterface as Git;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Api\JiraRestApi\SearchQuery\Builder as SearchQueryBuilder;
use Technodelight\Jira\Console\Argument\IssueKeyResolver\Guesser;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\IssueCollection;
use Technodelight\Jira\Domain\Project;

/** @SuppressWarnings(PHPMD.StaticAccess) */
class InteractiveIssueSelector
{
    public function __construct(
        private readonly Api $jira,
        private readonly Git $git,
        private readonly QuestionHelper $questionHelper,
        private readonly Guesser $guesser
    ) {
    }

    public function chooseIssue(InputInterface $input, OutputInterface $output): Issue
    {
        $issues = $this->retrieveIssuesToChooseFrom();
        $options = $this->assembleOptionsForQuestionHelper($issues);
        $index = $this->ask($input, $output, $options);
        if ($this->shouldEnterManually($options, $index)) {
            return $this->retrieveIssueFromManualInput($input, $output);
        }
        return $issues->findByIndex($index);
    }

    private function ask(InputInterface $input, OutputInterface $output, array $options): int
    {
        $answer = $this->questionHelper->ask($input, $output, new ChoiceQuestion(
            PHP_EOL . '<comment>Choose an issue (or press Ctrl+C to quit):</>',
            $options
        ));
        return array_search($answer, $options);
    }

    private function retrieveIssueFromManualInput(InputInterface $input, OutputInterface $output): Issue
    {
        $question = new Question(
            '<comment>Please enter issue key (or press Ctrl+C to quit):</> '
        );
        $question->setAutocompleterValues(
            array_map(
                function (Project $project) {
                    return $project->key();
                },
                $this->jira->projects(10)
            )
        );
        $jira = $this->jira;
        $question->setValidator(function ($issueKey) use ($jira) {
            $issue = $jira->retrieveIssue(IssueKey::fromString(strtoupper(trim($issueKey))));

            return $issue->key();
        });
        $issueKey = $this->questionHelper->ask($input, $output, $question);

        return $this->jira->retrieveIssue($issueKey);
    }

    public function retrieveIssuesToChooseFrom(?string $searchString = null): IssueCollection
    {
        $issuesToChooseFrom = IssueCollection::createEmpty();
        $this->collectIssuesFromHistory($issuesToChooseFrom);
        $this->collectRecentlyUpdatedIssues($issuesToChooseFrom);
        $this->collectIssuesWithSearchString($searchString, $issuesToChooseFrom);
        $this->collectIssuesFromLocalBranches($issuesToChooseFrom, $searchString);

        return $issuesToChooseFrom;
    }

    private function assembleOptionsForQuestionHelper(IssueCollection $issues): array
    {
        $options = array_values(array_map(
            function (Issue $issue) {
                return '<info>' . $issue->issueKey() . '</info> ' . strtr($issue->summary(), [':' => '']);
            },
            iterator_to_array($issues)
        ));
        $options[] = '<comment><enter manually></comment>';

        return $options;
    }

    private function shouldEnterManually(array $options, int $index): bool
    {
        return $index === count($options) - 1;
    }

    private function collectIssuesFromHistory(IssueCollection $issuesToChooseFrom): void
    {
        $issueHistory = $this->jira->search(
            SearchQueryBuilder::factory()
                              ->issueKeyInHistory()
                              ->orderDesc('created')
                              ->assemble()
        );
        $issueHistory->limit(10);
        $issuesToChooseFrom->merge($issueHistory);
    }

    private function collectIssuesFromLocalBranches(
        IssueCollection $issuesToChooseFrom,
        ?string $searchString = null
    ): void {
        $localBranches = $this->git->branches('feature/', false);
        if (!empty($localBranches)) {
            $issueKeys = [];
            foreach ($localBranches as $branch) {
                if ($branch->isRemote()) {
                    continue;
                }

                try {
                    $issueKey = $this->guesser->guessIssueKey($searchString, $branch);
                    if ($issueKey && !$issuesToChooseFrom->has($issueKey)) {
                        $issueKeys[] = $issueKey;
                    }
                } catch (\Exception $e) {
                    // ignore
                }
            }
            if (!empty($issueKeys)) {
                $issuesFromBranch = $this->jira->search(
                    SearchQueryBuilder::factory()
                                      ->issueKey($issueKeys)
                                      ->assemble()
                );
                $issuesToChooseFrom->merge($issuesFromBranch);
            }
        }
    }

    private function collectRecentlyUpdatedIssues(IssueCollection $issuesToChooseFrom): void
    {
        $issuesToChooseFrom->merge(
            $this->jira->search(
                SearchQueryBuilder::factory()
                    ->updated('startOfWeek()', 'endOfDay()')
                    ->assigneeWas('currentUser()')
                    ->assemble()
            )
        );
    }

    private function collectIssuesWithSearchString(?string $searchString, IssueCollection $issuesToChooseFrom): void
    {
        $searchString = trim((string)$searchString);
        if (!empty($searchString)) {
            try {
                $issuesToChooseFrom->merge(
                    $this->jira->search(sprintf('summary ~ "%s"', $searchString))
                );
            } catch (\Exception $e) {
                //@TODO ignore for now
            }
        }
    }
}
