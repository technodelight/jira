<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Api\JiraRestApi\SearchQuery\Builder as SearchQueryBuilder;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;
use Technodelight\Jira\Domain\Project;

class InteractiveIssueSelector
{
    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private $jira;
    /**
     * @var \Symfony\Component\Console\Helper\QuestionHelper
     */
    private $questionHelper;

    public function __construct(Api $jira, QuestionHelper $questionHelper)
    {
        $this->jira = $jira;
        $this->questionHelper = $questionHelper;
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
            PHP_EOL . '<comment>Choose an issue to log time to (or press Ctrl+C to quit):</>',
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
        return $this->jira->search(
            SearchQueryBuilder::factory()
                ->issueKeyInHistory()
                ->assemble()
        );
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

}
