<?php

namespace Technodelight\Jira\Renderer\Dashboard;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Dashboard\Collection;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Renderer\DashboardRenderer;

class ProjectStats implements DashboardRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\DateHelper
     */
    private $dateHelper;

    public function __construct(DateHelper $dateHelper)
    {
        $this->dateHelper = $dateHelper;
    }

    public function render(OutputInterface $output, Collection $collection)
    {
        if (!$collection->count()) {
            return;
        }

        $projects = [];
        $issuesCount = [];
        foreach ($collection as $worklogs) {
            foreach ($worklogs as $worklog) {
                /** @var $worklog \Technodelight\Jira\Domain\Worklog */
                $issue = $worklog->issue();
                if (!isset($projects[$issue->project()->key()])) {
                    $projects[$issue->project()->key()] = 0;
                    $issuesCount[$issue->project()->key()] = [];
                }
            }
        }

        foreach ($collection as $worklogs) {
            foreach ($worklogs as $worklog) {
                $projects[$worklog->issue()->project()->key()]+= $worklog->timeSpentSeconds();
                $issuesCount[$worklog->issue()->project()->key()][] = $worklog->issueKey();
            }
        }

        foreach ($projects as $project => $timeSpent) {
            $output->writeln(
                sprintf('<info>%s</>: %d %s, %s',
                    $project,
                    count(array_unique($issuesCount[$project])),
                    count(array_unique($issuesCount[$project])) == 1 ? 'issue' : 'issues',
                    $this->dateHelper->secondsToHuman($timeSpent)
                )
            );
        }
    }

}
