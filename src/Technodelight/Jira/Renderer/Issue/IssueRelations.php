<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\SymfonyRgbOutputFormatter\PaletteOutputFormatterStyle;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueLink;
use Technodelight\Jira\Domain\Status;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;

class IssueRelations implements IssueRenderer
{
    public function __construct(private readonly TemplateHelper $templateHelper)
    {
    }

    public function render(OutputInterface $output, Issue $issue): void
    {
        if ($parent = $issue->parent()) {
            $output->writeln($this->templateHelper->tabulate($this->renderTasks([$parent], 'parent')));
        }
        if ($subtasks = $issue->subtasks()) {
            $output->writeln($this->templateHelper->tabulate($this->renderTasks(iterator_to_array($subtasks), 'subtasks')));
        }
        if ($links = $issue->links()) {
            $output->writeln($this->templateHelper->tabulate($this->renderTasks($links, 'links')));
        }
    }

    private function renderTasks(array $tasks, $header)
    {
        $rows = [
            sprintf('<comment>%s:</comment> %s', $header, count($tasks) > 1 ? sprintf('(%d)', count($tasks)) : '')
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
            '<info>%s</> %s %s <fg=black>%s</>',
            $related->issueKey(),
            $this->formatStatus($related->status()),
            $related->summary(),
            $related->url()
        );
    }

    private function renderLink(IssueLink $link)
    {
        return sprintf(
            '<comment>%s</> <info>%s</> %s %s <fg=black>%s</> <fg=black>%s</>',
            $link->isInward() ? $link->type()->inward() : $link->type()->outward(),
            $link->isInward() ? $link->inwardIssue()->key() : $link->outwardIssue()->key(),
            $this->formatStatus($link->isInward() ? $link->inwardIssue()->status() : $link->outwardIssue()->status()),
            $link->isInward() ? $link->inwardIssue()->summary() : $link->outwardIssue()->summary(),
            $link->id(),
            $link->isInward() ? $link->inwardIssue()->url() : $link->outwardIssue()->url()
        );
    }

    private function formatStatus(Status $status): string
    {
        $style = new PaletteOutputFormatterStyle;
        $style->setForeground('black');
        $style->setBackground($status->statusCategoryColor());
        return $style->apply(sprintf(' %s ', $status));
    }
}
