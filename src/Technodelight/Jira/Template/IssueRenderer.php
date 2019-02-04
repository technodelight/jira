<?php

namespace Technodelight\Jira\Template;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RenderersConfiguration;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;
use Technodelight\Jira\Renderer\Board\Renderer as BoardRenderer;

class IssueRenderer
{
    /**
     * @var \Technodelight\Jira\Renderer\IssueRenderer[]
     */
    private $renderers;
    /**
     * @var RenderersConfiguration
     */
    private $configuration;

    private $listMode = null;
    /**
     * @var BoardRenderer
     */
    private $boardRenderer;

    public function __construct(array $renderers, FormatterHelper $formatterHelper, RenderersConfiguration $configuration, BoardRenderer $boardRenderer)
    {
        $this->renderers = $renderers;
        $this->formatterHelper = $formatterHelper;
        $this->configuration = $configuration;
        $this->boardRenderer = $boardRenderer;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param  IssueCollection $issues
     * @param mixed $mode
     */
    public function renderIssues(OutputInterface $output, IssueCollection $issues, $mode = false)
    {
        if (is_array($mode) && isset($mode['board']) && $mode['board'] === true) {
            $this->boardRenderer->render($output, $issues);
        } else {
            $this->listMode = true;
            $groupedIssues = $this->groupByParent(iterator_to_array($issues));
            foreach ($groupedIssues as $issueGroup) {
                $output->writeln(
                    $this->formatterHelper->formatBlock($issueGroup['parentInfo'], 'fg=black;bg=white', true) . PHP_EOL
                );
                foreach ($issueGroup['issues'] as $issue) {
                    $this->render($output, $issue, $mode);
                }
            }
            $this->renderStats($output, $issues);
            $this->listMode = null;
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @param bool|array $mode when array, it should be a list of options
     */
    public function render(OutputInterface $output, Issue $issue, $mode = false)
    {
        if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET) {
            $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
            $this->renderer('minimal')->render($output, $issue);
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        } else {
            $this->renderer($mode)->render($output, $issue);
        }
    }

    private function renderStats(OutputInterface $output, IssueCollection $issues)
    {
        $output->writeln('');
        $output->writeln(
            sprintf(
                '<info>%s issues listed of %d</info>',
                $issues->startAt() > 0 ? sprintf('%d - %d', $issues->startAt(), $issues->startAt() + $issues->count()) : $issues->count(),
                $issues->total()
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
            $group = $issue->parent() ? (string) $issue->parent()->issueKey() : 'Other issues';
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

    /**
     * @param array|bool $mode
     * @return mixed|\Technodelight\Jira\Renderer\IssueRenderer
     */
    private function renderer($mode = false)
    {
        if ($mode === false) {
            return $this->rendererByMode($this->configuration->preferredListRenderer());
        } else if ($mode === true) {
            return $this->rendererByMode($this->configuration->preferredViewRenderer());
        } else if (is_string($mode)) {
            return $this->rendererByMode($mode);
        } else if (is_array($mode)) {
            $supportedRenderersToCheck = array_keys(array_intersect_key($this->renderers, $mode));
            foreach ($supportedRenderersToCheck as $supportedRenderer) {
                if (isset($mode[$supportedRenderer]) && $mode[$supportedRenderer] === true) {
                    return $this->rendererByMode($supportedRenderer);
                }
            }
            return $this->rendererByMode($this->listMode ? $this->configuration->preferredListRenderer() : $this->configuration->preferredViewRenderer());
        }
        throw new \RuntimeException(sprintf(':\'( Cannot determine renderer mode. Argument was: %s', var_export($mode, true)));
    }

    private function rendererByMode($modeName)
    {
        if (isset($this->renderers[$modeName])) {
            return $this->renderers[$modeName];
        }

        throw new \InvalidArgumentException(sprintf('Cannot find renderer mode %s', $modeName));
    }
}
