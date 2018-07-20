<?php

namespace Technodelight\Jira\Helper;

use Hoa\Console\Readline\Autocompleter\Word;
use Hoa\Console\Readline\Readline;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration\BranchNameGeneratorConfiguration;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\GitBranchnameGenerator\PatternPrepare;
use Technodelight\Jira\Helper\GitBranchnameGenerator\StringCleaner;

class GitBranchnameGenerator
{
    /**
     * @var BranchNameGeneratorConfiguration
     */
    private $config;
    /**
     * @var ExpressionLanguage
     */
    private $expression;
    /**
     * @var PatternPrepare
     */
    private $patternPrepare;
    /**
     * @var StringCleaner
     */
    private $stringCleaner;

    public function __construct(
        BranchNameGeneratorConfiguration $config,
        ExpressionLanguage $expression,
        PatternPrepare $patternPrepare,
        StringCleaner $stringCleaner
    )
    {
        $this->config = $config;
        $this->patternPrepare = $patternPrepare;
        $this->stringCleaner = $stringCleaner;
        $this->expression = $expression;
    }

    public function fromIssue(Issue $issue)
    {
        return $this->patternFromData(
            ['issueKey' => $issue->issueKey(), 'summary' => $this->stringCleaner->clean($issue->summary()), 'issue' => $issue]
        );
    }

    /**
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @return string
     */
    public function fromIssueWithAutocomplete(Issue $issue)
    {
        $readline = new Readline;
        $readline->setAutocompleter(new Word($this->getAutocompleteWords($issue)));

        $prefix = $this->patternFromData(
            ['issueKey' => $issue->issueKey(), 'summary' => '', 'issue' => $issue]
        );
        $summary = $readline->readLine($prefix);

        return $this->patternFromData(
            ['issueKey' => $issue->issueKey(), 'summary' => $this->stringCleaner->clean($summary), 'issue' => $issue]
        );
    }

    private function patternFromData($expressionData)
    {
        foreach ($this->config->patterns() as $expression => $pattern) {
            if ($this->expression->evaluate($expression, $expressionData)) {
                return $this->patternPrepare->prepare($pattern, $expressionData);
            }
        }

        return '';
    }

    /**
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @return array
     */
    private function getAutocompleteWords(Issue $issue)
    {
        return array_merge(
            explode($this->config->separator(), $this->stringCleaner->clean($issue->summary())),
            ['fix', 'add', 'change', 'remove', 'implement']
        );
    }
}
