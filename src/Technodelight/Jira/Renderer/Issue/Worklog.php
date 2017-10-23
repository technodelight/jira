<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog as IssueWorklog;
use Technodelight\Jira\Domain\WorklogCollection;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;
use Technodelight\SecondsToNone;

class Worklog implements IssueRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;
    /**
     * @var \Technodelight\SecondsToNone
     */
    private $secondsToNone;

    public function __construct(TemplateHelper $templateHelper, SecondsToNone $secondsToNone)
    {
        $this->templateHelper = $templateHelper;
        $this->secondsToNone = $secondsToNone;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        $worklogs = $issue->worklogs();
        if ($worklogs->count()) {
            $output->writeln($this->tab('<comment>worklogs:</comment>'));
            $this->renderWorklogs($output, $worklogs);
        }
    }

    public function renderWorklogs(OutputInterface $output, WorklogCollection $worklogs)
    {
        foreach ($worklogs as $worklog) {
            $this->renderWorklog($output, $worklog);
        }
    }

    public function renderWorklog(OutputInterface $output, IssueWorklog $worklog)
    {
        $row = [$this->worklogHeader($worklog)];
        if ($comment = $worklog->comment()) {
            $row[] = $this->tab(trim($comment));
        }
        $output->writeln($this->tab($this->tab($row)));
    }

    /**
     * @param \Technodelight\Jira\Domain\Worklog $worklog
     * @return string
     */
    private function worklogHeader(IssueWorklog $worklog)
    {
        return <<<EOF
<comment>{$worklog->author()->displayName()}</comment>: {$this->human($worklog->timeSpentSeconds())} at {$worklog->date()->format('Y-m-d H:i:s')} <fg=black>({$worklog->id()})</>
EOF;
    }

    private function human($seconds)
    {
        return $this->secondsToNone->secondsToHuman($seconds);
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
