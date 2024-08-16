<?php

namespace Technodelight\Jira\Renderer\Dashboard;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\DateHelper;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Domain\WorklogCollection;
use Technodelight\Jira\Renderer\DashboardRenderer;
use Technodelight\Jira\Domain\DashboardCollection as Collection;

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

    public function render(OutputInterface $output, Collection $collection): void
    {
        if (!$collection->count()) {
            return;
        }

        $projects = [];
        $issuesCount = [];
        foreach ($collection as $workLogs) {
            foreach ($workLogs as $workLog) {
                /** @var $workLog \Technodelight\Jira\Domain\Worklog */
                $issue = $workLog->issue();
                if (!isset($projects[$issue->project()->key()])) {
                    $projects[$issue->project()->key()] = 0;
                    $issuesCount[$issue->project()->key()] = [];
                }
            }
        }

        foreach ($collection as $workLogs) {
            foreach ($workLogs as $workLog) {
                $projects[$workLog->issue()->project()->key()]+= $workLog->timeSpentSeconds();
                $issuesCount[$workLog->issue()->project()->key()][] = $workLog->issueKey();
            }
        }

        $aliasedIssues = [];
        foreach ($collection as $workLogs) {
            foreach ($workLogs->issueKeys() as $issueKey) {
                $alias = $this->aliasesConfiguration->issueKeyToAlias($issueKey);
                if ($alias != $issueKey) {
                    if (!isset($aliasedIssues[$alias])) {
                        $aliasedIssues[$alias] = $workLogs->filterByIssueKey($issueKey);
                        continue;
                    }

                    /** @var WorklogCollection $aliasedWorkLogs */
                    $aliasedWorkLogs = $aliasedIssues[$alias];
                    $aliasedWorkLogs->merge($workLogs->filterByIssueKey($issueKey));
                }
            }
        }

        foreach ($projects as $project => $timeSpent) {
            $output->writeln(
                strtr(
                    '<info>{project}</>: {count} {phrase}, {timeSpent}',
                    [
                        '{project}' => $project,
                        '{count}' => count(array_unique($issuesCount[$project])),
                        '{phrase}' => count(array_unique($issuesCount[$project])) == 1 ? 'issue' : 'issues',
                        '{timeSpent}' => $this->dateHelper->secondsToHuman($timeSpent)
                    ]
                )
            );
        }

        if (!empty($aliasedIssues)) {
            $output->writeln([
                '',
                'Where you spent the following amount of work on the aliased issues:'
            ]);
            foreach ($aliasedIssues as $alias => $workLogs) {
                /** @var WorklogCollection $workLogs */
                $output->writeln(
                    strtr(
                        '<comment>{alias}</>: {timeSpent}',
                        [
                            '{alias}' => $alias,
                            '{timeSpent}' => $this->dateHelper->secondsToHuman($workLogs->totalTimeSpentSeconds())
                        ]
                    )
                );
                foreach ($workLogs as $workLog) {
                    $output->writeln(
                        strtr(
                            '  - {timeSpent} on {date}, {comment} <fg=black>({id}})</>',
                            [
                                '{timeSpent}' => $this->dateHelper->secondsToHuman($workLog->timeSpentSeconds()),
                                '{date}' => $workLog->date()->format('Y-m-d'),
                                '{comment}' => $workLog->comment(),
                                '{id}' => $workLog->id()
                            ]
                        )
                    );
                }
            }
        }
    }

}
