<?php

namespace Technodelight\Jira\Helper;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration\BranchNameGeneratorConfiguration;
use Technodelight\Jira\Connector\HoaConsole\Aggregate;
use Technodelight\Jira\Connector\HoaConsole\Word;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\GitBranchnameGenerator\PatternPrepare;
use Technodelight\Jira\Helper\GitBranchnameGenerator\StringCleaner;

class GitBranchnameGenerator
{
    private BranchNameGeneratorConfiguration $config;
    private ExpressionLanguage $expression;
    private PatternPrepare $patternPrepare;
    private StringCleaner $stringCleaner;

    public function __construct(
        BranchNameGeneratorConfiguration $config,
        ExpressionLanguage $expression,
        PatternPrepare $patternPrepare,
        StringCleaner $stringCleaner
    ) {
        $this->config = $config;
        $this->patternPrepare = $patternPrepare;
        $this->stringCleaner = $stringCleaner;
        $this->expression = $expression;
    }

    public function fromIssue(Issue $issue): string
    {
        return $this->patternFromData(
            ['issueKey' => $issue->issueKey(), 'summary' => $this->stringCleaner->clean($issue->summary()), 'issue' => $issue]
        );
    }

    public function fromIssueWithAutocomplete(Issue $issue, InputInterface $input, OutputInterface $output): string
    {
        $q = new QuestionHelper();
        $basePatternForPrompt = $this->patternFromData(
            ['issueKey' => $issue->issueKey(), 'summary' => '', 'issue' => $issue]
        );
        $question = new Question($basePatternForPrompt);
        $question->setAutocompleterCallback(new Aggregate([
            new Word($this->getAutocompleteWords($issue))
        ]));
        $summary = $q->ask($input, $output, $question);

        return $this->patternFromData(
            ['issueKey' => $issue->issueKey(), 'summary' => $this->stringCleaner->clean($summary), 'issue' => $issue]
        );
    }

    private function patternFromData($expressionData): string
    {
        foreach ($this->config->patterns() as $expression => $pattern) {
            if ($this->expression->evaluate($expression, $expressionData)) {
                return $this->patternPrepare->prepare($pattern, $expressionData);
            }
        }

        return '';
    }

    private function getAutocompleteWords(Issue $issue): array
    {
        return array_merge(
            explode($this->config->separator(), $this->stringCleaner->clean($issue->summary())),
            ['fix', 'add', 'change', 'remove', 'implement']
        );
    }
}
