<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\Worklog;
use Technodelight\Jira\Api\IssueCollection;
use Technodelight\Jira\Console\Command\AbstractCommand;

class DashboardCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('dashboard')
            ->setDescription('Show your daily/weekly dashboard')
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Show your worklogs for the given date, could be "yesterday", "last week", "2015-09-28", today by default',
                'today'
            )
            ->addOption(
                'week',
                'w',
                InputOption::VALUE_NONE,
                'Display worklog for the week defined by date argument'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $input->getArgument('date');
        $from = $this->defineFrom($date, $input->getOption('week'));
        $to = $this->defineTo($date, $input->getOption('week'));

        $jira = $this->getService('technodelight.jira.api');
        $dateHelper = $this->getService('technodelight.jira.date_helper');
        $pluralizeHelper = $this->getService('technodelight.jira.pluralize_helper');
        $issues = $jira->retrieveIssuesHavingWorklogsForUser($from, $to);
        $user = $jira->user();

        if (count($issues) == 0) {
            $output->writeln("You don't have any issues at the moment, which has worklog in range");
            return;
        }

        $worklogs = $jira->retrieveIssuesWorklogs($this->issueKeys($issues));
        $logs = $this->filterLogsByDateAndUser($worklogs, $from, $to, $user['displayName']);

        $totalTimeInRange = $dateHelper->humanToSeconds($input->getOption('week') ? '5d' : '1d');
        $summary = 0;
        foreach ($logs as $log) {
            $summary+= $log->timeSpentSeconds();
        }

        $output->writeln(
            sprintf(
                'You have been working on %d %s %s' . PHP_EOL,
                count($issues),
                $pluralizeHelper->pluralize('issue', count($issues)),
                $from == $to ? "on $from" : "from $from to $to"
            )
        );

        $progress = $this->createProgressbar($output, $totalTimeInRange);
        $progress->setProgress($summary);
        $progress->display();
        $output->writeln('');
        if ($input->getOption('week')) {
            $this->renderWeek($output, $logs);
        } else {
            $this->renderDay($output, $logs);
        }

        $output->writeln(
            sprintf(
                'Total time logged: %s of %s (%0.2f%%, %s)' . PHP_EOL,
                $dateHelper->secondsToHuman($summary),
                $input->getOption('week') ? '5d' : '1d',
                ($summary / $totalTimeInRange) * 100,
                $this->missingTimeText($totalTimeInRange - $summary)
            )
        );
    }

    private function renderWeek(OutputInterface $output, array $logs)
    {
        $rows = [];
        $headers = [];
        $dates = [];
        foreach ($logs as $log) {
            $dates[$log->date()] = date(
                'l',
                strtotime($log->date())
            );
        }
        ksort($dates);
        $headers = ['Issue' => 'Issue'] + $dates;

        foreach ($logs as $log) {
            if (!isset($rows[$log->issueKey()])) {
                $rows[$log->issueKey()] = array_fill_keys(array_keys($headers), '');
                $rows[$log->issueKey()]['Issue'] = $log->issueKey();
            }
            if (!isset($rows[$log->issueKey()][$log->date()])) {
                $rows[$log->issueKey()][$log->date()] = '';
            }
            $rows[$log->issueKey()][$log->date()].= sprintf(
                PHP_EOL . '%s %s',
                $log->timeSpent(),
                $this->shortenWorklogComment($log->comment())
            );
            $rows[$log->issueKey()][$log->date()] = trim($rows[$log->issueKey()][$log->date()]);
            if (!isset($rows['Sum'][$log->date()])) {
                $rows['Sum'][$log->date()] = 0;
            }
            $rows['Sum'][$log->date()]+= $log->timeSpentSeconds();
        }

        // sum logged / max seconds
        $sum = $rows['Sum'];
        unset($rows['Sum']);
        ksort($rows);
        $dateHelper = $this->getService('technodelight.jira.date_helper');
        $aDay = $dateHelper->humanToSeconds('1d');
        foreach ($sum as $date => $timeSpentSeconds) {
            if ($aDay == $timeSpentSeconds) {
                $sum[$date] = '1d';
            } else {
                $sum[$date] = $dateHelper->secondsToHuman($timeSpentSeconds);
            }
        }
        ksort($sum);
        array_unshift($sum, 'Total');
        $rows[] = new TableSeparator();
        $rows['Sum'] = $sum;

        // use the style for this table
        $table = new Table($output);
        $table
            ->setHeaders(array_values($headers))
            ->setRows($rows);
        $table->render($output);
    }

    private function renderDay(OutputInterface $output, array $logs)
    {
        $dateHelper = $this->getService('technodelight.jira.date_helper');
        $templateHelper = $this->getService('technodelight.jira.template_helper');

        $rows = [];
        $totalTimes = [];
        foreach ($logs as $log) {
            if (!isset($rows[$log->issueKey()])) {
                $rows[$log->issueKey()] = [];
            }
            if (!isset($totalTimes[$log->issueKey()])) {
                $totalTimes[$log->issueKey()] = $dateHelper->humanToSeconds($log->timeSpent());
            } else {
                $totalTimes[$log->issueKey()]+= $dateHelper->humanToSeconds($log->timeSpent());
            }
            $rows[$log->issueKey()][] = ['worklogId' => $log->id(), 'comment' => $log->comment(), 'timeSpent' => $log->timeSpent()];
        }

        $jira = $this->getService('technodelight.jira.api');
        $issues = $jira->retrieveIssues(array_keys($rows));

        $output->writeln('');
        foreach ($rows as $issueKey => $records) {
            $issue = $issues->find($issueKey);
            // parent issue
            $parentInfo = '';
            if ($parent = $issue->parent()) {
                $parentInfo = sprintf('<bg=yellow>[%s %s]</>', $parent->issueKey(), $parent->summary());
            }
            // issue header
            if (count($records) > 1) {
                $output->writeln(
                    sprintf('<info>%s</info> %s: (%s) ' . $parentInfo, $issueKey, $issue->summary(), $dateHelper->secondsToHuman($totalTimes[$issueKey]))
                );
            } else {
                $output->writeln(sprintf('<info>%s</info> %s: ' . $parentInfo, $issueKey, $issue->summary()));
            }

            // logs
            foreach ($records as $record) {
                $output->writeln(
                    sprintf(
                        '    <comment>%s</comment>: %s <fg=black>(%d)</>',
                        $record['timeSpent'],
                        $record['comment'],
                        $record['worklogId']
                    )
                );
            }
        }
        $output->writeln('');
    }

    private function createProgressbar(OutputInterface $output, $steps)
    {
        // render progress bar
        $progress = new ProgressBar($output, $steps);
        $progress->setFormat('%bar% %percent%%');
        $progress->setBarCharacter('<bg=green> </>');
        $progress->setEmptyBarCharacter('<bg=white> </>');
        $progress->setProgressCharacter('<bg=green> </>');
        $progress->setBarWidth(50);
        return $progress;
    }

    private function orderByDate(array $rows)
    {
        uasort($rows, function($a, $b) {
            if ($a[2] == $b[2]) {
                return 0;
            }

            return $a[2] < $b[2] ? -1 : 1;
        });

        return $rows;
    }

    private function filterLogsByDateAndUser(array $logs, $from, $to, $username)
    {
        return array_filter(
            $logs,
            function(Worklog $log) use ($from, $to, $username) {
                if ($log->author() != $username) {
                    return false;
                }
                $date = date('Y-m-d', strtotime($log->date()));
                if ($date >= $from && $date <= $to) {
                    return $log;
                }
            }
        );
    }

    private function defineFrom($date, $weekFlag)
    {
        if ($weekFlag) {
            $date = $this->defineWeekStr($date, 1);
        }
        return date(
            'Y-m-d',
            strtotime($date)
        );
    }

    private function defineTo($date, $weekFlag)
    {
        if ($weekFlag) {
            $date = $this->defineWeekStr($date, 5);
        }
        return date(
            'Y-m-d',
            strtotime($date)
        );
    }

    private function defineWeekStr($date, $day)
    {
        $dayOfWeek = date('N', strtotime($date));
        $operator = $day < $dayOfWeek ? '-' : '+';
        $delta = abs($dayOfWeek - $day);
        return sprintf('%s %s %s day', $date, $operator, $delta);
    }

    private function issueKeys($issues)
    {
        $issueKeys = [];
        foreach ($issues as $issue) {
            $issueKeys[] = $issue->issueKey();
        }
        return $issueKeys;
    }

    private function shortenWorklogComment($text, $length = 15)
    {
        $wrapped = explode(PHP_EOL, wordwrap($text, $length));
        return array_shift($wrapped) . (count($wrapped) >= 1 ? '..' : '');
    }

    private function missingTimeText($missingTime)
    {
        $dateHelper = $this->getService('technodelight.jira.date_helper');
        if ($missingTime >= 0) {
            return sprintf(
                '%s missing',
                $dateHelper->secondsToHuman($missingTime)
            );
        }
        return sprintf(
            '<bg=red>%s overtime</>',
            $dateHelper->secondsToHuman(abs($missingTime))
        );
    }
}
