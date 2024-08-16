<?php

namespace Technodelight\Jira\Template;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RenderersConfiguration;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;
use Technodelight\Jira\Renderer\Board\Renderer as BoardRenderer;
use Technodelight\Jira\Renderer\IssueRenderer as Renderer;

/** @TODO: refactor me */
class IssueRenderer
{
    private ?bool $listMode = null;

    public function __construct(
        private readonly array $renderers,
        private readonly FormatterHelper $formatterHelper,
        private readonly RenderersConfiguration $configuration,
        private readonly BoardRenderer $boardRenderer
    ) {
    }

    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    public function renderIssues(OutputInterface $output, IssueCollection $issues, array|bool $mode = false): void
    {
        if (is_array($mode) && isset($mode['board']) && $mode['board'] === true) {
            $this->boardRenderer->render($output, $issues);
            return;
        }

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

    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    public function render(OutputInterface $output, Issue $issue, array|bool $mode = false): void
    {
        if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET) {
            $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
            $this->renderer('minimal')->render($output, $issue);
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
            return;
        }

        $this->renderer($mode)->render($output, $issue);
    }

    private function renderStats(OutputInterface $output, IssueCollection $issues): void
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
    private function groupByParent(array $issues): array
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

        uksort($groupedIssues, fn(string $groupName) => $groupName <=> 'Other issues');

        return $groupedIssues;
    }

    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    private function renderer(array|bool $mode = false): Renderer
    {
        if ($mode === false) {
            return $this->rendererByMode($this->configuration->preferredListRenderer());
        } else if ($mode === true) {
            return $this->rendererByMode($this->configuration->preferredViewRenderer());
        } else if (is_string($mode)) {
            return $this->rendererByMode($mode);
        } else if (is_array($mode)) {
            $supportedRenderers = array_keys(array_intersect_key($this->renderers, $mode));
            foreach ($supportedRenderers as $supportedRenderer) {
                if (isset($mode[$supportedRenderer]) && $mode[$supportedRenderer] === true) {
                    return $this->rendererByMode($supportedRenderer);
                }
            }
            return $this->rendererByMode($this->listMode ? $this->configuration->preferredListRenderer() : $this->configuration->preferredViewRenderer());
        }
        throw new RuntimeException(sprintf(':\'( Cannot determine renderer mode. Argument was: %s', var_export($mode, true)));
    }

    private function rendererByMode($modeName)
    {
        if (isset($this->renderers[$modeName])) {
            return $this->renderers[$modeName];
        }

        throw new InvalidArgumentException(sprintf('Cannot find renderer mode %s', $modeName));
    }
}
