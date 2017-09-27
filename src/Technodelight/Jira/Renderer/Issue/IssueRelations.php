<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Renderer;

class IssueRelations implements Renderer
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;

    public function __construct(TemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        if ($parent = $issue->parent()) {
            $output->writeln($this->templateHelper->tabulate($this->renderTasks([$parent], 'parent')));
        }
        if ($subtasks = $issue->subtasks()) {
            $output->writeln($this->templateHelper->tabulate($this->renderTasks($subtasks, 'subtasks')));
        }
    }

    private function renderTasks(array $tasks, $header)
    {
        $rows = [
            sprintf('<comment>%s:</comment>', $header)
        ];
        foreach ($tasks as $task) {
            $rows[] = $this->templateHelper->tabulate($this->renderRelatedTask($task));
        }
        return $rows;
    }

    private function renderRelatedTask(Issue $related)
    {
        return sprintf('<info>%s</> %s <fg=black>(%s)</>', $related->issueKey(), $related->summary(), $related->url());
    }
}
