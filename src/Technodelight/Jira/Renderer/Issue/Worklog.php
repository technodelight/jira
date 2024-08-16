<?php

declare(strict_types=1);

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
    public function __construct(
        private readonly TemplateHelper $templateHelper,
        private readonly SecondsToNone $secondsToNone,
        private readonly Wordwrap $wordwrap
    ) {
    }

    public function render(OutputInterface $output, Issue $issue): void
    {
        $workLogs = $issue->worklogs();
        if ($workLogs->count() > 0) {
            $output->writeln($this->tab('<comment>worklogs:</comment>'));
            $this->renderWorklogs($output, $workLogs);
        }
    }

    public function renderWorklogs(OutputInterface $output, WorklogCollection $workLogs): void
    {
        $count = $workLogs->count();
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_NORMAL) {
            $count = 10;
            $workLogs->orderByCreatedDateDesc();
        }

        $displayed = 0;
        foreach ($workLogs as $workLog) {
            if ($displayed < $count) {
                $this->renderWorkLog($output, $workLog);
                $displayed++;
            }
        }
    }

    public function renderWorkLog(OutputInterface $output, IssueWorklog $workLog): void
    {
        $row = [$this->workLogHeader($workLog)];
        $comment = $workLog->comment();
        if (!empty($comment)) {
            $row[] = $this->tab(trim($this->wordwrap->wrap($comment)));
        }
        $output->writeln($this->tab($this->tab($row)));
    }

    private function workLogHeader(IssueWorklog $workLog): string
    {
        return strtr(
            '<info>{author}</info> <comment>[{timeSpent}]</> {when}: <fg=black>({id}) ({timestamp})</>',
            [
                '{author}' => $workLog->author()->displayName(),
                '{timeSpent}' => $this->human($workLog->timeSpentSeconds()),
                '{when}' => $this->ago($workLog->date()),
                '{id}' => $workLog->id(),
                '{timestamp}' => $workLog->date()->format('Y-m-d H:i:s'),
            ]
        );
    }

    private function human($seconds): ?string
    {
        return $this->secondsToNone->secondsToHuman($seconds);
    }

    private function tab($string): string
    {
        return $this->templateHelper->tabulate($string);
    }

    private function ago(DateTime $date): string
    {
        return TimeAgo::fromDateTime($date)->inWords();
    }
}
