<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Issue;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration\TransitionsConfiguration;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Transition;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;

class Transitions implements IssueRenderer
{
    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    public function __construct(
        private readonly TransitionsConfiguration $configuration,
        private readonly Api $api,
        private readonly TemplateHelper $templateHelper,
        private readonly bool $fullMode = true
    ) {
    }

    public function render(OutputInterface $output, Issue $issue): void
    {
        $transitions = $this->api->retrievePossibleTransitionsForIssue($issue->key());
        if (!empty($transitions)) {
            if ($this->fullMode) {
                $output->writeln(
                    [
                        $this->tab('<comment>transitions:</comment>'),
                        $this->tab($this->tab($this->showTransitionsFull($transitions, $issue)))
                    ]
                );
                return;
            }

            $commands = $this->showTransitionsShort($transitions);
            $output->writeln(
                $this->tab(
                    sprintf(
                        '<comment>transitions:</comment> %s%s',
                        join(', ', $commands),
                        count($commands) !== count($transitions) ? sprintf(', %d more', count($transitions) - count($commands)) : ''
                    )
                )
            );
        }
    }

    private function showTransitionsFull(array $transitions, Issue $issue): array
    {
        return array_map(function(Transition $transition) use ($issue) {
            $command = $this->checkHasCommand($transition);
            return sprintf(
                '<info>%s</> <fg=cyan>%s</> <fg=black>%s</>',
                $transition->name(),
                $command ? sprintf('[%s]', $command) : '',
                $command ? sprintf('(jira %s %s)', $command, $issue->key()) : ''
            );
        }, $transitions);
    }

    private function showTransitionsShort(array $transitions): array
    {
        return array_filter(array_map(function(Transition $transition) {
            $command = $this->checkHasCommand($transition);

            if ($command) {
                return sprintf('<fg=cyan>%s</>', $command);
            }

            return null;
        }, $transitions));
    }

    private function checkHasCommand(Transition $transition): string
    {
        try {
            return 'workflow:' . $this->configuration->commandForTransition($transition->name());
        } catch (Exception $e) {
            return '';
        }
    }

    private function tab(array|string $string): string
    {
        return $this->templateHelper->tabulate($string);
    }
}
