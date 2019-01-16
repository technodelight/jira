<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration\TransitionsConfiguration;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Transition;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;

class Transitions implements IssueRenderer
{
    /**
     * @var TransitionsConfiguration
     */
    private $configuration;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var TemplateHelper
     */
    private $templateHelper;
    /**
     * @var bool
     */
    private $fullMode;

    public function __construct(TransitionsConfiguration $configuration, Api $api, TemplateHelper $templateHelper, $fullMode = true)
    {
        $this->configuration = $configuration;
        $this->api = $api;
        $this->templateHelper = $templateHelper;
        $this->fullMode = $fullMode;
    }

    public function render(OutputInterface $output, Issue $issue)
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
            } else {
                $commands = $this->showTransitionsShort($transitions, $issue);
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
    }

    /**
     * @param Transition[] $transitions
     */
    private function showTransitionsFull(array $transitions, Issue $issue)
    {
        $self = $this;
        return array_map(function(Transition $transition) use ($self, $issue) {
            $command = $self->checkHasCommand($transition);
            return sprintf(
                '<info>%s</> <fg=cyan>%s</> <fg=black>%s</>',
                $transition->name(),
                $command ? sprintf('[%s]', $command) : '',
                $command ? sprintf('(jira %s %s)', $command, $issue->key()) : ''
            );
        }, $transitions);
    }

    private function showTransitionsShort(array $transitions, Issue $issue)
    {
        $self = $this;
        return array_filter(array_map(function(Transition $transition) use ($self, $issue) {
            $command = $self->checkHasCommand($transition);

            if ($command) {
                return sprintf('<fg=cyan>%s</>', $command);
            }

            return null;
        }, $transitions));
    }

    /**
     * @param Transition $transition
     * @return string
     */
    private function checkHasCommand(Transition $transition)
    {
        try {
            return 'workflow:' . $this->configuration->commandForTransition($transition->name());
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @param string[]|string $string
     * @return string
     */
    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
