<?php

namespace Technodelight\Jira\Helper;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration\BranchNameGeneratorConfiguration;
use Technodelight\Jira\Connector\Autocompleter;
use Technodelight\Jira\Connector\HoaConsole\Aggregate;
use Technodelight\Jira\Connector\HoaConsole\Word;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\GitBranchnameGenerator\PatternPrepare;
use Technodelight\Jira\Helper\GitBranchnameGenerator\StringCleaner;

class GitBranchnameGenerator
{
    public function __construct(
        private readonly BranchNameGeneratorConfiguration $config,
        private readonly ExpressionLanguage $expression,
        private readonly PatternPrepare $patternPrepare,
        private readonly StringCleaner $stringCleaner,
        private readonly Autocompleter\Factory $autocompleterFactory
    ) {
    }

    public function fromIssue(Issue $issue): string
    {
        return $this->patternFromData(
            [
                'issueKey' => $issue->issueKey(),
                'summary' => $this->stringCleaner->clean($issue->summary()),
                'issue' => $issue
            ]
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

    public function fromIssueWithAutocomplete(Issue $issue, InputInterface $input, OutputInterface $output): string
    {
        $basePatternForPrompt = $this->patternFromData(
            ['issueKey' => $issue->issueKey(), 'summary' => '', 'issue' => $issue]
        );
        $autocompleter = $this->autocompleterFactory->create($input, $output);
        $autocompleter->setAutocomplete(new Aggregate([new Word($this->getAutocompleteWords($issue))]));
        $summary = $autocompleter->read($basePatternForPrompt);


        return $this->patternFromData(
            ['issueKey' => $issue->issueKey(), 'summary' => $this->stringCleaner->clean($summary), 'issue' => $issue]
        );
    }

    private function getAutocompleteWords(Issue $issue): array
    {
        return array_merge(
            explode($this->config->separator(), $this->stringCleaner->clean($issue->summary())),
            ['fix', 'add', 'change', 'remove', 'implement']
        );
    }
}
