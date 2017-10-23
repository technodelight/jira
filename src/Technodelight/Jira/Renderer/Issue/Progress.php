<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;
use Technodelight\SecondsToNone;

class Progress implements IssueRenderer
{
    const PROGRESS_FORMAT_IN_PROGRESS = '<info>%message%</> %bar% %percent%%';
    const PROGRESS_FORMAT_DEFAULT = '<info>%message%</>';
    const PROGRESS_BAR_WIDTH = 50;
    const PROGRESS_BAR_WORKED_CHAR = '<bg=green> </>';
    const PROGRESS_BAR_REMAINING_CHAR = '<bg=white> </>';
    /**
     * @var \Technodelight\SecondsToNone
     */
    private $secondsToNone;
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;

    public function __construct(SecondsToNone $secondsToNone, TemplateHelper $templateHelper)
    {
        $this->secondsToNone = $secondsToNone;
        $this->templateHelper = $templateHelper;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        $this->getProgressBar($output, $issue)->display();
        $output->writeln('');
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @return \Symfony\Component\Console\Helper\ProgressBar
     */
    private function getProgressBar(OutputInterface $output, Issue $issue)
    {
        $issueProgress = $issue->progress();
        $progress = new ProgressBar($output, $issueProgress['total']);
        $progress->setFormat($this->getProgressBarFormat($issue));
        $progress->setBarCharacter(self::PROGRESS_BAR_WORKED_CHAR);
        $progress->setEmptyBarCharacter(self::PROGRESS_BAR_REMAINING_CHAR);
        $progress->setProgressCharacter(self::PROGRESS_BAR_WORKED_CHAR);
        $progress->setBarWidth(self::PROGRESS_BAR_WIDTH);
        $progress->setProgress($issueProgress['progress']);
        $progress->setMessage($this->tab($this->tab($this->getProgressBarMessage($issue))));

        return $progress;
    }

    private function getProgressBarMessage(Issue $issue)
    {
        $estimate = $this->secondsToNone->secondsToHuman($issue->estimate());
        $spent = $this->secondsToNone->secondsToHuman($issue->timeSpent());
        $remaining = $this->secondsToNone->secondsToHuman($issue->remainingEstimate());
        return sprintf(
            '%sspent: %s%s',
            $estimate != 'none' ? 'estimate: ' . $estimate . ', ' : '',
            $spent,
            $remaining != 'none' && !empty($remaining) ? ', remaining: ' . $remaining : ''
        );
    }

    /**
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @return string
     */
    private function getProgressBarFormat(Issue $issue)
    {
        if (strtolower($issue->status()) != 'in progress') {
            return self::PROGRESS_FORMAT_DEFAULT;
        }
        return self::PROGRESS_FORMAT_IN_PROGRESS;
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
