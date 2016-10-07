<?php

namespace Technodelight\Jira\Template;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\Issue;
use Technodelight\Jira\Api\IssueCollection;
use Technodelight\Jira\Api\Worklog;
use Technodelight\Jira\Console\Application;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Helper\GitBranchnameGenerator;
use Technodelight\Jira\Helper\GitHelper;
use Technodelight\Jira\Helper\HubHelper;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Template\CommentRenderer;
use Technodelight\Jira\Template\WorklogRenderer;
use Technodelight\Simplate;

class IssueRenderer
{
    const PROGRESS_FORMAT_IN_PROGRESS = '        <info>%message%</> %bar% %percent%%';
    const PROGRESS_FORMAT_DEFAULT = '        <info>%message%</>';

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

    /**
     * @var HubHelper
     */
    private $hub;

    /**
     * @var string
     */
    private $viewsDir;

    /**
     * Templates per issue type
     * @var array
     */
    private $templates = [
        'Default' => 'Issues/default.template',
    ];

    /**
     * Formats for various issue types
     * @var array
     */
    private $issueTypeFormats = [
        'Default' => '<fg=black;bg=blue>%s</>',
        'Defect' => '<error>%s</error>',
        'Bug' => '<error>%s</error>',
    ];

    /**
     * @var array
     */
    private $worklogs = [];

    /**
     * @var array
     */
    private $hubCache;


    public function __construct(
        Application $app,
        FormatterHelper $formatterHelper,
        TemplateHelper $templateHelper,
        DateHelper $dateHelper,
        GitHelper $gitHelper,
        HubHelper $hubHelper,
        GitBranchnameGenerator $gitBranchnameGenerator,
        WorklogRenderer $worklogRenderer,
        CommentRenderer $commentRenderer
    )
    {
        $this->viewsDir = $app->directory('views');
        $this->formatterHelper = $formatterHelper;
        $this->templateHelper = $templateHelper;
        $this->dateHelper = $dateHelper;
        $this->git = $gitHelper;
        $this->hub = $hubHelper;
        $this->gitBranchnameGenerator = $gitBranchnameGenerator;
        $this->worklogRenderer = $worklogRenderer;
        $this->commentRenderer = $commentRenderer;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
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
                $this->render($issue);
            }
        }
    }

    public function render(Issue $issue, $templateNameOverride = null)
    {
        if (is_null($templateNameOverride)) {
            $template = $this->getTemplateInstanceForIssue($issue);
        } else {
            $template = $this->getTemplateInstance($templateNameOverride);
        }

        $content = $template->render(
            [
                'issueNumber' => $issue->ticketNumber(),
                'issueType' => $this->formatIssueType($issue->issueType()),
                'url' => $issue->url(),
                'summary' => $this->tabulate(wordwrap($issue->summary()), 8),
                // 'estimate' => $this->secondsToHuman($issue->estimate()),
                // 'spent' => $this->secondsToHuman($issue->timeSpent()),
                // 'remaining' => $this->secondsToHuman($issue->remainingEstimate()),
                'progress' => $this->renderProgress($issue),

                'description' => $this->tabulate($this->shorten(wordwrap($issue->description()))),
                'environment' => $issue->environment(),
                'reporter' => $issue->reporter(),
                'assignee' => $issue->assignee(),
                'reporter' => $issue->reporter(),
                'parent' => $this->tabulate($this->renderParentTask($issue), 8),
                'subTasks' => $this->tabulate($this->renderSubTasks($issue), 8),

                'branches' => $this->tabulate(implode(PHP_EOL, $this->retrieveGitBranches($issue)), 8),
                'hubIssues' => $this->tabulate(implode(PHP_EOL, $this->retrieveHubIssues($issue)), 8),
                'verbosity' => $this->output->getVerbosity(),
                'worklogs' => $this->tabulate(
                    $this->worklogRenderer->renderWorklogs($this->issueWorklogs($issue))
                ),
                'comments' => $this->tabulate($this->renderComments($issue)),
            ]
        );

        $output = implode(
            PHP_EOL,
            array_filter(
                array_map('rtrim', explode(PHP_EOL, $content))
            )
        ) . PHP_EOL;
        $this->output->writeln($output);
    }

    private function formatIssueType($issueType)
    {
        $format = isset($this->issueTypeFormats[$issueType])
            ? $issueType : 'Default';
        return sprintf($this->issueTypeFormats[$format], $issueType);
    }

    private function renderComments(Issue $issue)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            return $this->commentRenderer->renderComments($issue->comments());
        }

        return '';
    }

    private function renderParentTask(Issue $issue)
    {
        if ($parent = $issue->parent()) {
            return $this->renderRelatedTask($parent);
        }

        return '';
    }

    private function renderSubTasks(Issue $issue)
    {
        if ($subtasks = $issue->subtasks()) {
            $rendered = [];
            foreach ($subtasks as $subtask) {
                $rendered[] = $this->renderRelatedTask($subtask);
            }
            return join(PHP_EOL, $rendered);
        }

        return '';
    }

    private function renderRelatedTask(Issue $related)
    {
        return sprintf('<info>%s</> %s (%s)', $related->issueKey(), $related->summary(), $related->url());
    }

    private function renderProgress(Issue $issue)
    {
        if (strtolower($issue->status()) != 'in progress') {
            $format = self::PROGRESS_FORMAT_DEFAULT;
        } else {
            $format = self::PROGRESS_FORMAT_IN_PROGRESS;
        }

        $out = new BufferedOutput($this->output->getVerbosity(), true, $this->output->getFormatter());
        $issueProgress = $issue->progress();
        $progress = new ProgressBar($out, $issueProgress['total']);
        $progress->setFormat($format);
        $progress->setBarCharacter('<bg=green> </>');
        $progress->setEmptyBarCharacter('<bg=white> </>');
        $progress->setProgressCharacter('<bg=green> </>');
        $progress->setBarWidth(50);
        $progress->setProgress($issueProgress['progress']);
        $estimate = $this->secondsToHuman($issue->estimate());
        $spent = $this->secondsToHuman($issue->timeSpent());
        $remaining = $this->secondsToHuman($issue->remainingEstimate());
        $progress->setMessage(
            sprintf(
                '%sspent: %s%s',
                $estimate != 'none' ? 'estimate: ' . $estimate . ', ' : '',
                $spent,
                $remaining != 'none' && !empty($remaining) ? ', remaining: ' . $remaining : ''
            )
        );
        $progress->display();
        return $out->fetch();
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
            $this->templates[$templateId] = Simplate::fromFile(
                $this->viewsDir . DIRECTORY_SEPARATOR . $this->templates[$templateId]
            );
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
        if ($this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            return [];
        }

        $branches = $this->git->branches($issue->ticketNumber());
        if (empty($branches)) {
            return [$this->gitBranchnameGenerator->fromIssue($issue) . ' (generated)'];
        } else {
            return array_unique(
                array_map(
                    function(array $branchData) {
                        return sprintf('%s (%s)', $branchData['name'], $branchData['remote'] ? 'remote' : 'local');
                    },
                    $branches
                )
            );
        }
    }

    private function retrieveHubIssues(Issue $issue)
    {
        if ($this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            return [];
        }
        if (!isset($this->hubCache)) {
            $this->hubCache = $this->hub->issues();
        }

        $matchingIssues = array_filter($this->hubCache, function($hubIssue) use($issue) {
            return strpos($hubIssue['text'], $issue->issueKey()) === 0;
        });

        return array_map(
            function($hubIssue) {
                return $hubIssue['link'];
            },
            $matchingIssues
        );
    }

    private function shorten($text, $maxLines = 2)
    {
        if ($this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
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
