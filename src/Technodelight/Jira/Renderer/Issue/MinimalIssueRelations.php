<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;

class MinimalIssueRelations implements IssueRenderer
{
    private TemplateHelper $templateHelper;

    public function __construct(TemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public function render(OutputInterface $output, Issue $issue): void
    {
        $output->writeln(
            $this->templateHelper->tabulate(implode(',', $this->getLinkedIssueIds($issue)))
        );
    }

    /**
     * @param Issue $issue
     * @return array|string[]
     */
    protected function getLinkedIssueIds(Issue $issue): array
    {
        return array_map(static function (Issue $issue) {
            return $issue->id();
        }, array_filter(array_merge([$issue->parent()], $issue->subtasks(), $issue->links())));
    }
}
