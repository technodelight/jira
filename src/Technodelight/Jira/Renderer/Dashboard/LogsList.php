<?php

namespace Technodelight\Jira\Renderer\Dashboard;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Dashboard\Collection;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\WorklogCollection;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\DashboardRenderer;

class LogsList implements DashboardRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\DateHelper
     */
    private $dateHelper;
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;

    public function __construct(DateHelper $dateHelper, TemplateHelper $templateHelper)
    {
        $this->dateHelper = $dateHelper;
        $this->templateHelper = $templateHelper;
    }

    public function render(OutputInterface $output, Collection $collection)
    {
        if (!$collection->count()) {
            return;
        }

        $this->renderList($output, $collection);
    }

    private function renderList(OutputInterface $output, Collection $collection)
    {
        $daysCount = $collection->days();

        foreach ($collection as $day => $worklogs) {
            // group issues by issue key
            /** @var $worklogs WorklogCollection */
            $totalTimes = array_fill_keys($worklogs->issueKeys(), 0);
            $rows = array_fill_keys($worklogs->issueKeys(), []);
            $issues = $worklogs->issues();

            foreach ($worklogs as $worklog) {
                /** @var $worklog Worklog */
                $rows[(string) $worklog->issueKey()][] = $worklog;
                $totalTimes[(string) $worklog->issueKey()]+= $worklog->timeSpentSeconds();
            }

            if ($daysCount > 1) {
                $output->writeln([
                    '',
                    sprintf(
                        '<comment>%s</> <fg=white;options=bold>%s</> (%d %s%s)',
                        $day->format('Y-m-d'),
                        $day->format('l'),
                        $worklogs->issueCount(),
                        $worklogs->issueCount() == 1 ? 'issue' : 'issues',
                        $worklogs->issueCount() != $worklogs->count() ? sprintf(', %d worklogs', $worklogs->count()) : ''
                    ),
                    ''
                ]);
            }

            if (!$worklogs->count()) {
                $output->writeln($this->tab('No issues logged'));
                continue;
            }

            foreach ($rows as $issueKey => $records) {
                /** @var Issue $issue */
                $issue = $issues->find($issueKey);

                // parent issue
                $parentInfo = '';
                if ($parent = $issue->parent()) {
                    $parentInfo = sprintf('<bg=yellow>[%s %s]</>', $parent->issueKey(), $parent->summary());
                }
                // issue header
                if (count($records) > 1) {
                    $output->writeln(
                        sprintf('<info>%s</info> %s: (%s) ' . $parentInfo, $issueKey, $issue->summary(), $this->dateHelper->secondsToHuman($totalTimes[$issueKey]))
                    );
                } else {
                    $output->writeln(
                        sprintf('<info>%s</info> %s: ' . $parentInfo, $issueKey, $issue->summary())
                    );
                }

                // logs
                foreach ($records as $record) {
                    /** @var $record Worklog */
                    $output->writeln(
                        $this->tab(
                            sprintf(
                                '<comment>%s</comment>: %s <fg=black>(%d)</>',
                                $this->dateHelper->secondsToHuman($record->timeSpentSeconds()),
                                trim($record->comment()),
                                $record->id()
                            )
                        )
                    );
                }
            }
            if($daysCount > 1) {
                $output->writeln(
                    sprintf(
                        'Total time logged: %s of %s (%0.2f%%, %s)',
                        $this->dateHelper->secondsToHuman($worklogs->totalTimeSpentSeconds()),
                        '1d',
                        ($worklogs->totalTimeSpentSeconds() / $this->dateHelper->humanToSeconds('1d')) * 100, // percentage
                        $this->missingTimeText($this->dateHelper->humanToSeconds('1d') - $worklogs->totalTimeSpentSeconds())
                    )
                );
                $output->writeln('');
            }
        }
    }


    private function missingTimeText($missingTime)
    {
        if ($missingTime >= 0) {
            return sprintf(
                '%s missing',
                $this->dateHelper->secondsToHuman($missingTime)
            );
        }
        return sprintf(
            '<bg=red>%s overtime</>',
            $this->dateHelper->secondsToHuman(abs($missingTime))
        );
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
