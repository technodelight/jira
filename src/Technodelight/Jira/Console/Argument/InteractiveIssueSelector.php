<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Technodelight\GitShell\Api as Git;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Api\JiraRestApi\SearchQuery\Builder as SearchQueryBuilder;
use Technodelight\Jira\Console\Argument\IssueKeyResolver\Guesser;
use Technodelight\Jira\Console\IssueStats\StatCollector;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;
use Technodelight\Jira\Domain\Project;

class InteractiveIssueSelector
{
    /**
     * @var Api
     */
    private $jira;
    /**
     * @var Git
     */
    private $git;
    /**
     * @var StatCollector
     */
    private $statCollector;
    /**
     * @var QuestionHelper
     */
    private $questionHelper;
    /**
     * @var Guesser
     */
    private $guesser;

    public function __construct(Api $jira, Git $git, StatCollector $statCollector, QuestionHelper $questionHelper, Guesser $guesser)
    {
        $this->jira = $jira;
        $this->git = $git;
        $this->statCollector = $statCollector;
        $this->questionHelper = $questionHelper;
        $this->guesser = $guesser;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return \Technodelight\Jira\Domain\Issue
     */
    public function chooseIssue(InputInterface $input, OutputInterface $output)
    {
        $issues = $this->retrieveIssuesToChooseFrom();
        $options = $this->assembleOptionsForQuestionHelper($issues);
        $index = $this->ask($input, $output, $options);
        if ($this->shouldEnterManually($options, $index)) {
            return $this->retrieveIssueFromManualInput($input, $output);
        }
        return $issues->findByIndex($index);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param array $options
     * @return int
     */
    private function ask(InputInterface $input, OutputInterface $output, array $options)
    {
        $answer = $this->questionHelper->ask($input, $output, new ChoiceQuestion(
            PHP_EOL . '<comment>Choose an issue (or press Ctrl+C to quit):</>',
            $options
        ));
        return array_search($answer, $options);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return \Technodelight\Jira\Domain\Issue
     */
    private function retrieveIssueFromManualInput(InputInterface $input, OutputInterface $output)
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
            $issue = $jira->retrieveIssue(strtoupper(trim($issueKey)));

            return $issue->key();
        });
        $issueKey = $this->questionHelper->ask($input, $output, $question);

        return $this->jira->retrieveIssue($issueKey);
    }

    /**
     * @return \Technodelight\Jira\Domain\IssueCollection
     */
    private function retrieveIssuesToChooseFrom()
    {
        $issuesToChooseFrom = IssueCollection::createEmpty();
        $this->collectIssuesFromHistory($issuesToChooseFrom);
        $this->collectIssuesFromStat($issuesToChooseFrom);
        $this->collectIssuesFromLocalBranches($issuesToChooseFrom);

        return $issuesToChooseFrom;
    }

    /**
     * @param \Technodelight\Jira\Domain\IssueCollection $issues
     * @return array
     */
    private function assembleOptionsForQuestionHelper(IssueCollection $issues)
    {
        $options = array_map(
            function (Issue $issue) {
                return '<info>' . $issue->issueKey() . '</info> ' . $issue->summary();
            },
            iterator_to_array($issues)
        );
        $options[] = '<comment><enter manually></comment>';

        return $options;
    }

    /**
     * @param array $options
     * @param int $index
     * @return bool
     */
    private function shouldEnterManually(array $options, $index)
    {
        return $index == count($options) - 1;
    }

    /**
     * @param IssueCollection $issuesToChooseFrom
     */
    private function collectIssuesFromHistory(IssueCollection $issuesToChooseFrom)
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

    /**
     * @param $issuesToChooseFrom
     * @return array
     */
    private function collectIssuesFromStat(IssueCollection $issuesToChooseFrom)
    {
        if ($usedIssues = $this->statCollector->all()) {
            $usedIssues->orderByMostRecent();
            $usedIssues->orderByTotal();
            $issueKeys = [];
            foreach ($usedIssues->issueKeys(10) as $issueKey) {
                if (!$issuesToChooseFrom->has($issueKey)) {
                    $issueKeys[] = $issueKey;
                }
            }

            if (!empty($issueKeys)) {
                $issueStats = $this->jira->search(
                    SearchQueryBuilder::factory()
                                      ->issueKey($usedIssues->issueKeys(10))
                                      ->assemble()
                );
                $issuesToChooseFrom->merge($issueStats);
            }
        }
    }

    /**
     * @param IssueCollection $issuesToChooseFrom
     */
    private function collectIssuesFromLocalBranches(IssueCollection $issuesToChooseFrom)
    {
        if ($localBranches = $this->git->branches('feature/', false)) {
            $issueKeys = [];
            foreach ($localBranches as $branch) {
                if ($branch->isRemote()) {
                    continue;
                }

                try {
                    $issueKey = $this->guesser->guessIssueKey(null, $branch);
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

}
