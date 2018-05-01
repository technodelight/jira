<?php

namespace Technodelight\Jira\Renderer\Issue;

use DateTime;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog as IssueWorklog;
use Technodelight\Jira\Domain\WorklogCollection;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Helper\Wordwrap;
use Technodelight\Jira\Renderer\IssueRenderer;
use Technodelight\SecondsToNone;
use Technodelight\TimeAgo;

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
    /**
     * @var \Technodelight\Jira\Helper\Wordwrap
     */
    private $wordwrap;

    public function __construct(TemplateHelper $templateHelper, SecondsToNone $secondsToNone, Wordwrap $wordwrap)
    {
        $this->templateHelper = $templateHelper;
        $this->secondsToNone = $secondsToNone;
        $this->wordwrap = $wordwrap;
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
        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $count = $worklogs->count();
        } else {
            $count = 20;
            $worklogs->orderByCreatedDateDesc();
        }
        $displayed = 0;
        foreach ($worklogs as $worklog) {
            if ($displayed < $count) {
                $this->renderWorklog($output, $worklog);
                $displayed++;
            }
        }
    }

    public function renderWorklog(OutputInterface $output, IssueWorklog $worklog)
    {
        $row = [$this->worklogHeader($worklog)];
        if ($comment = $worklog->comment()) {
            $row[] = $this->tab(trim($this->wordwrap->wrap($comment)));
        }
        $output->writeln($this->tab($this->tab($row)));
    }

    /**
     * @param \Technodelight\Jira\Domain\Worklog $worklog
     * @return string
     */
    private function worklogHeader(IssueWorklog $worklog)
    {
        return <<<EOL
<info>{$worklog->author()->displayName()}</info> <comment>[{$this->human($worklog->timeSpentSeconds())}]</> {$this->ago($worklog->date())}: <fg=black>({$worklog->id()}) ({$worklog->date()->format('Y-m-d H:i:s')})</>
EOL;
    }

    private function human($seconds)
    {
        return $this->secondsToNone->secondsToHuman($seconds);
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }

    private function ago(DateTime $date)
    {
        return TimeAgo::fromDateTime($date)->inWords();
    }
}
