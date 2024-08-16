<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\SymfonyRgbOutputFormatter\PaletteOutputFormatterStyle;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueLink;
use Technodelight\Jira\Domain\Status;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;

class IssueRelations implements IssueRenderer
{
    public function __construct(
        private readonly TemplateHelper $templateHelper,
        private readonly Header $header
    ) {}

    public function render(OutputInterface $output, Issue $issue): void
    {
        $parent = $issue->parent();
        $subtasks = $issue->subtasks();
        $links = $issue->links();

        if (!empty($parent)) {
            $output->writeln($this->templateHelper->tabulate($this->renderTasks([$parent], 'parent')));
        }
        if ($subtasks->count() > 0) {
            $output->writeln($this->templateHelper->tabulate($this->renderTasks(iterator_to_array($subtasks), 'subtasks')));
        }
        if (!empty($links)) {
            $output->writeln($this->templateHelper->tabulate($this->renderTasks($links, 'links')));
        }
    }

    private function renderTasks(array $tasks, string $header): array
    {
        $count = count($tasks);
        $rows = [
            sprintf('<comment>%s:</comment> %s', $header, $count > 1 ? sprintf('(%d)', $count) : '')
        ];
        foreach ($tasks as $task) {
            $content = $task instanceof IssueLink ? $this->renderLink($task) : $this->renderIssue($task);
            $rows[] = $this->templateHelper->tabulate($content);
        }
        return $rows;
    }

    private function renderLink(IssueLink $link): string
    {
        return strtr(
            '<comment>{issue}</> <info>{otherIssue}</> {status} {summary} <fg=black>{id} {url}</>',
            [
                '{issue}' => $link->isInward() ? $link->type()->inward() : $link->type()->outward(),
                '{otherIssue}' => $link->isInward() ? $link->inwardIssue()->key() : $link->outwardIssue()->key(),
                '{status}' => $this->formatStatus($link->isInward() ? $link->inwardIssue()->status() : $link->outwardIssue()->status()),
                '{summary}' => $link->isInward() ? $link->inwardIssue()->summary() : $link->outwardIssue()->summary(),
                '{id}' => $link->id(),
                '{url}' => $link->isInward() ? $link->inwardIssue()->url() : $link->outwardIssue()->url(),
            ]
        );
    }

    private function renderIssue(Issue $issue): string
    {
        $output = new BufferedOutput();
        $this->header->render($output, $issue);
        return $output->fetch();
    }

    private function formatStatus(Status $status): string
    {
        $style = new PaletteOutputFormatterStyle;
        $style->setForeground('black');
        $style->setBackground($status->statusCategoryColor());
        return $style->apply(sprintf(' %s ', $status));
    }
}
