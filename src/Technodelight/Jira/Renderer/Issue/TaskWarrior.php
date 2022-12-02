<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\TaskWarrior\Api;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;

class TaskWarrior implements IssueRenderer
{
    /**
     * @var TemplateHelper
     */
    private $templateHelper;
    /**
     * @var Api
     */
    private $task;

    public function __construct(TemplateHelper $templateHelper, Api $task)
    {
        $this->templateHelper = $templateHelper;
        $this->task = $task;
    }

    public function render(OutputInterface $output, Issue $issue): void
    {
        if (!$this->task->isSupported()) {
            return;
        }

        $tasks = $this->task->list(sprintf('project:%1$s.%1$d or project:%1$s-%1$d', $issue->project()->key(), $issue->sequenceNumber()));
        if (!empty($tasks)) {
            $output->writeln($this->templateHelper->tabulateWithLevel('<comment>taskwarrior tasks:</comment>'));
            $output->writeln($this->templateHelper->tabulateWithLevel($tasks, 2));
        }
    }
}
