<?php

namespace Technodelight\Jira\Template;

use Symfony\Component\Console\Output\OutputInterface;

use Technodelight\Jira\Api\SearchResultList;
use Technodelight\Jira\Api\Issue;
use Technodelight\Jira\Template\Template;
use Technodelight\Jira\Helper\GitHelper;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Helper\GitBranchnameGenerator;

class SearchResultRenderer
{
    private $git;
    private $gitBranchnameGenerator;
    private $templateHelper;
    private $dateHelper;
    private $defaultTemplate;
    private $defectTemplate;

    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->templateHelper = new TemplateHelper;
        $this->dateHelper = new DateHelper;
        $this->git = new GitHelper;
        $this->gitBranchnameGenerator = new GitBranchnameGenerator;
        $this->defaultTemplate = Template::fromFile('Technodelight/Jira/Resources/views/Issues/default.template');
        $this->defectTemplate = Template::fromFile('Technodelight/Jira/Resources/views/Issues/defect.template');
        $this->output = $output;
    }

    public function renderIssues(SearchResultList $issues)
    {
        $content = '';
        foreach ($issues as $issue) {
            $variables = [
                'issueNumber' => $issue->ticketNumber(),
                'issueType' => $issue->issueType(),
                'url' => $issue->url(),
                'summary' => $this->templateHelper->tabulate(wordwrap($issue->summary())),
                'estimate' => $this->dateHelper->secondsToHuman($issue->estimate()),
                'spent' => $this->dateHelper->secondsToHuman($issue->timeSpent()),

                'description' => $this->shorten(wordwrap($issue->description())),
                'environment' => $issue->environment(),
                'reporter' => $issue->reporter(),
                'assignee' => $issue->assignee(),

                'branches' => $this->templateHelper->tabulate(implode(PHP_EOL, $this->retrieveGitBranches($issue))),
                'verbosity' => $this->output->getVerbosity(),
            ];
            if ($issue->issueType() == 'Defect') {
                $content.= $this->defectTemplate->render($variables);
            } else {
                $content.= $this->defaultTemplate->render($variables);
            }
        }

        $this->output->writeln(str_replace("\n\n", "\n", $content));
    }

    private function retrieveGitBranches(Issue $issue)
    {
        if ($this->output->getVerbosity() == 1) {
            return [];
        }

        $branches = $this->git->branches($issue->ticketNumber());
        if (empty($branches)) {
            $branches = [$this->gitBranchnameGenerator->fromIssue($issue) . ' (generated)'];
        }

        return $branches;
    }

    private function shorten($text, $maxLines = 2)
    {
        if ($this->output->getVerbosity() == 1) {
            $lines = explode(PHP_EOL, $text);
            $text = implode(
                PHP_EOL,
                array_filter(
                    array_map('trim', array_slice($lines, 0, $maxLines))
                )
            ) . (count($lines) > $maxLines ? '...' : '');
        }

        return $this->templateHelper->tabulate($text);
    }
}
