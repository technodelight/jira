<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueLink;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;

class IssueRelations implements IssueRenderer
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
        if ($links = $issue->links()) {
            $output->writeln($this->templateHelper->tabulate($this->renderTasks($links, 'links')));
        }
    }

    private function renderTasks(array $tasks, $header)
    {
        $rows = [
            sprintf('<comment>%s:</comment>', $header)
        ];
        foreach ($tasks as $task) {
            if ($task instanceof IssueLink) {
                $rows[] = $this->templateHelper->tabulate($this->renderLink($task));
            } else {
                $rows[] = $this->templateHelper->tabulate($this->renderRelatedTask($task));
            }
        }
        return $rows;
    }

    private function renderRelatedTask(Issue $related)
    {
        return sprintf(
            '<info>%s</> %s <fg=black>(%s)</>',
            $related->issueKey(),
            $related->summary(),
            $related->url()
        );
    }

    private function renderLink(IssueLink $link)
    {
        return sprintf(
            '<comment>%s</> <info>%s</> %s <fg=black>(%s)</>',
            $link->isInward() ? $link->type()->inward() : $link->type()->outward(),
            $link->isInward() ? $link->inwardIssue()->key() : $link->outwardIssue()->key(),
            $link->isInward() ? $link->inwardIssue()->summary() : $link->outwardIssue()->summary(),
            $link->isInward() ? $link->inwardIssue()->url() : $link->outwardIssue()->url()
        );
    }
}
