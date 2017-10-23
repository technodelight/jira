<?php

namespace Technodelight\Jira\Template;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;

class IssueRenderer
{
    /**
     * @var \Technodelight\Jira\Renderer\IssueRenderer
     */
    private $fullRenderer;
    /**
     * @var \Technodelight\Jira\Renderer\IssueRenderer
     */
    private $shortRenderer;
    /**
     * @var \Symfony\Component\Console\Helper\FormatterHelper
     */
    private $formatterHelper;
    /**
     * @var \Technodelight\Jira\Renderer\IssueRenderer
     */
    private $minimalRenderer;

    public function __construct($fullRenderer, $shortRenderer, $minimalRenderer, FormatterHelper $formatterHelper)
    {
        $this->fullRenderer = $fullRenderer;
        $this->shortRenderer = $shortRenderer;
        $this->minimalRenderer = $minimalRenderer;
        $this->formatterHelper = $formatterHelper;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param  IssueCollection $issues
     */
    public function renderIssues(OutputInterface $output, IssueCollection $issues)
    {
        $groupedIssues = $this->groupByParent(iterator_to_array($issues));
        foreach ($groupedIssues as $issueGroup) {
            $output->writeln(
                $this->formatterHelper->formatBlock($issueGroup['parentInfo'], 'fg=black;bg=white', true) . PHP_EOL
            );
            foreach ($issueGroup['issues'] as $issue) {
                $this->render($output, $issue);
            }
        }
        $this->renderStats($output, $issues);
    }

    public function render(OutputInterface $output, Issue $issue, $full = false)
    {
        if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET) {
            $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
            $this->minimalRenderer->render($output, $issue);
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        } else
        if ($full) {
            $this->fullRenderer->render($output, $issue);
        } else {
            $this->shortRenderer->render($output, $issue);
        }
    }

    private function renderStats(OutputInterface $output, IssueCollection $issues)
    {
        $output->writeln('');
        $output->writeln(
            sprintf(
                '<info>%d issues found</info>',
                $issues->count()
            )
        );
    }

    /**
     * @param  Issue[] $issues
     *
     * @return array issues grouped by parent
     */
    private function groupByParent(array $issues)
    {
        $groupedIssues = [];
        foreach ($issues as $issue) {
            $group = $issue->parent() ? $issue->parent()->issueKey() : 'Other issues';
            if (!isset($groupedIssues[$group])) {
                $groupedIssues[$group] = [
                    'parentInfo' => array_filter(
                        [
                            $group,
                            $issue->parent() ? $issue->parent()->summary() : '',
                            $issue->parent() ? $issue->parent()->url() : '',
                        ]
                    ),
                    'issues' => []
                ];
            }
            $groupedIssues[$group]['issues'][] = $issue;
        }

        uksort($groupedIssues, function ($a) {
            if ($a == 'Other issues') {
                return 1;
            }

            return 0;
        });

        return $groupedIssues;
    }
}
