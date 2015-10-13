<?php

namespace Technodelight\Jira\Template;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\Issue;
use Technodelight\Jira\Api\IssueCollection;
use Technodelight\Jira\Api\Worklog;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Helper\GitBranchnameGenerator;
use Technodelight\Jira\Helper\GitHelper;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Template\CommentRenderer;
use Technodelight\Jira\Template\Template;
use Technodelight\Jira\Template\WorklogRenderer;
use Technodelight\Simplate;

class IssueRenderer
{
    /**
     * @var GitHelper
     */
    private $git;

    /**
     * @var GitBranchnameGenerator
     */
    private $gitBranchnameGenerator;

    /**
     * @var TemplateHelper
     */
    private $templateHelper;

    /**
     * @var DateHelper
     */
    private $dateHelper;

    private $templates = [
        'Default' => 'Technodelight/Jira/Resources/views/Issues/default.template',
        'Defect' => 'Technodelight/Jira/Resources/views/Issues/defect.template',
        'Bug' => 'Technodelight/Jira/Resources/views/Issues/defect.template',
    ];

    /**
     * @var array
     */
    private $worklogs = [];

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    /**
     * @var WorklogRenderer
     */
    private $worklogRenderer;

    /**
     * @var CommentRenderer
     */
    private $commentRenderer;

    public function __construct(OutputInterface $output, FormatterHelper $formatterHelper)
    {
        $this->templateHelper = new TemplateHelper;
        $this->dateHelper = new DateHelper;
        $this->git = new GitHelper;
        $this->gitBranchnameGenerator = new GitBranchnameGenerator;
        $this->worklogRenderer = new WorklogRenderer;
        $this->commentRenderer = new CommentRenderer;

        $this->output = $output;
        $this->formatterHelper = $formatterHelper;
    }

    /**
     * @param array $worklogs
     *
     * @return $this
     */
    public function addWorklogs(array $worklogs)
    {
        $this->worklogs = $worklogs;

        return $this;
    }

    /**
     * @param  IssueCollection $issues
     */
    public function renderIssues(IssueCollection $issues)
    {
        $content = '';
        $groupedIssues = $this->groupByParent(iterator_to_array($issues));
        foreach ($groupedIssues as $issueGroup) {
            $this->output->writeln(
                $this->formatterHelper->formatBlock($issueGroup['parentInfo'], 'fg=black;bg=white', true) . PHP_EOL
            );
            foreach ($issueGroup['issues'] as $issue) {
                $this->output->writeln($this->render($issue));
            }
        }
    }

    private function render(Issue $issue, $templateNameOverride = null)
    {
        if (is_null($templateNameOverride)) {
            $template = $this->getTemplateInstanceForIssue($issue);
        } else {
            $template = $this->getTemplateInstance($templateNameOverride);
        }

        $content = $template->render(
            [
                'issueNumber' => $issue->ticketNumber(),
                'issueType' => $issue->issueType(),
                'url' => $issue->url(),
                'summary' => $this->tabulate(wordwrap($issue->summary()), 8),
                'estimate' => $this->secondsToHuman($issue->estimate()),
                'spent' => $this->secondsToHuman($issue->timeSpent()),

                'description' => $this->tabulate($this->shorten(wordwrap($issue->description()))),
                'environment' => $issue->environment(),
                'reporter' => $issue->reporter(),
                'assignee' => $issue->assignee(),

                'branches' => $this->tabulate(implode(PHP_EOL, $this->retrieveGitBranches($issue)), 8),
                'verbosity' => $this->output->getVerbosity(),
                'worklogs' => $this->tabulate(
                    $this->worklogRenderer->renderWorklogs($this->issueWorklogs($issue))
                ),
                'comments' => $this->tabulate($this->renderComments($issue)),
            ]
        );
        return implode(
            PHP_EOL,
            array_filter(
                array_map('rtrim', explode(PHP_EOL, $content))
            )
        ) . PHP_EOL;
    }

    private function renderComments(Issue $issue)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            return $this->commentRenderer->renderComments($issue->comments());
        }

        return '';
    }

    private function issueWorklogs(Issue $issue)
    {
        return array_filter($this->worklogs, function(Worklog $worklog) use ($issue) {
            return $worklog->issueKey() == $issue->issueKey();
        });
    }

    private function getTemplateInstanceForIssue(Issue $issue)
    {
        if (isset($this->templates[$issue->issueType()])) {
            return $this->getTemplateInstance($issue->issueType());
        }
        return $this->getTemplateInstance('Default');
    }

    private function getTemplateInstance($templateId)
    {
        if (!($this->templates[$templateId] instanceof Simplate)) {
            $this->templates[$templateId] = Template::fromFile($this->templates[$templateId]);
        }

        return $this->templates[$templateId];
    }

    private function wasParentDisplayed($parentIssueName)
    {
        if (!isset($this->displayedParentIssues[$parentIssueName])) {
            $this->displayedParentIssues[$parentIssueName] = true;
            return false;
        }

        return true;
    }

    /**
     * @param  Issue[]  $issues
     *
     * @return array issues grouped by parent
     */
    private function groupByParent(array $issues)
    {
        $groupedIssues = [];
        foreach ($issues as $issue) {
            $parent = $issue->parent() ?: false;
            $group = $parent ? $parent->ticketNumber() : 'Other issues';
            if (!isset($groupedIssues[$group])) {
                $groupedIssues[$group] = [
                    'parentInfo' => array_filter(
                        array(
                            $group,
                            $parent ? $parent->summary() : '',
                            $parent ? $parent->url() : '',
                        )
                    ),
                    'issues' => []
                ];
            }
            $groupedIssues[$group]['issues'][] = $issue;
        }

        uksort($groupedIssues, function($a) {
            if ($a == 'Other issues') {
                return 1;
            }
            return 0;
        });

        return $groupedIssues;
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

    private function tabulate($text, $pad = 4)
    {
        return $this->templateHelper->tabulate($text, $pad);
    }

    private function secondsToHuman($seconds)
    {
        return $this->dateHelper->secondsToHuman($seconds);
    }
}
