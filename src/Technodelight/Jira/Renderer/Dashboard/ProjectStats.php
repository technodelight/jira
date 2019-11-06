<?php

namespace Technodelight\Jira\Renderer\Dashboard;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\DateHelper;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Console\Dashboard\Collection;
use Technodelight\Jira\Renderer\DashboardRenderer;

class ProjectStats implements DashboardRenderer
{
    /**
     * @var DateHelper
     */
    private $dateHelper;
    /**
     * @var AliasesConfiguration
     */
    private $aliasesConfiguration;

    public function __construct(DateHelper $dateHelper, AliasesConfiguration $aliasesConfiguration)
    {
        $this->dateHelper = $dateHelper;
        $this->aliasesConfiguration = $aliasesConfiguration;
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

        $aliasedIssues = [];
        foreach ($collection as $worklogs) {
            foreach ($worklogs->issueKeys() as $issueKey) {
                $alias = $this->aliasesConfiguration->issueKeyToAlias($issueKey);
                if ($alias != $issueKey) {
                    if (!isset($aliasedIssues[$alias])) {
                        $aliasedIssues[$alias] = $worklogs->filterByIssueKey($issueKey);
                    } else {
                        /** @var \Technodelight\Jira\Domain\WorklogCollection $aliasedWorklogs */
                        $aliasedWorklogs = $aliasedIssues[$alias];
                        $aliasedWorklogs->merge($worklogs->filterByIssueKey($issueKey));
                    }
                }
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

        if (!empty($aliasedIssues)) {
            $output->writeln([
                '',
                'Where you spent the following amount of work on the aliased issues:'
            ]);
            foreach ($aliasedIssues as $alias => $worklogs) {
                /** @var \Technodelight\Jira\Domain\WorklogCollection $worklogs */
                $output->writeln(
                    sprintf(
                        '<comment>%s</>: %s',
                        $alias,
                        $this->dateHelper->secondsToHuman($worklogs->totalTimeSpentSeconds())
                    )
                );
                foreach ($worklogs as $worklog) {
                    $output->writeln(
                        sprintf(
                            '  - %s on %s, %s <fg=black>(%d)</>',
                            $this->dateHelper->secondsToHuman($worklog->timeSpentSeconds()),
                            $worklog->date()->format('Y-m-d'),
                            $worklog->comment(),
                            $worklog->id()
                        )
                    );
                }
            }
        }
    }

}
