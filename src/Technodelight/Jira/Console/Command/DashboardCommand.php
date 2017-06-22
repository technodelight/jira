<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\Api;
use Technodelight\Jira\Api\Issue;
use Technodelight\Jira\Api\WorklogCollection;
use Technodelight\Jira\Helper\DateHelper;

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
        $date = $this->dateArgument($input);
        $from = $this->defineFrom($date, $input->getOption('week'));
        $to = $this->defineTo($date, $input->getOption('week'));

        /** @var Api $jira */
        $jira = $this->getService('technodelight.jira.api');
        $dateHelper = $this->getService('technodelight.jira.date_helper');
        $pluralizeHelper = $this->getService('technodelight.jira.pluralize_helper');
        $user = $jira->user();

        $issues = $jira->findUserIssuesWithWorklogs($from, $to, $user->name());

        if (count($issues) == 0) {
            $output->writeln("You don't have any issues at the moment, which has worklog in range");
            return;
        }

        // flatten worklogs
        $logs = WorklogCollection::createEmpty();
        foreach ($issues as $issue) {
            $logs->merge($issue->worklogs());
        }

        $totalTimeInRange = $dateHelper->humanToSeconds($input->getOption('week') ? '5d' : '1d');
        $summary = $logs->totalTimeSpentSeconds();

        // pure output of this command follows
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
        $this->renderLogs($output, $logs, $input->getOption('week'));
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

    private function renderLogs(OutputInterface $output, WorklogCollection $logs, $isWeek)
    {
        if ($isWeek) {
            $this->renderWeek($output, $logs);
        } else {
            $this->renderDay($output, $logs);
        }
    }

    private function renderWeek(OutputInterface $output, WorklogCollection $logs)
    {
        $rows = [];
        $headers = ['Issue'];
        foreach ($logs as $log) {
            $day = date('l', strtotime($log->date()));
            $dayNo = date('N', strtotime($log->date()));
            $headers[$dayNo] = $day;
        }
        ksort($headers);

        foreach ($logs as $log) {
            $dayNo = date('N', strtotime($log->date()));

            if (!isset($rows[$log->issueKey()])) {
                $rows[$log->issueKey()] = array_fill_keys(array_keys($headers), '');
                $rows[$log->issueKey()][0] = $log->issueKey();
            }
            if (!isset($rows[$log->issueKey()][$dayNo])) {
                $rows[$log->issueKey()][$dayNo] = '';
            }
            $rows[$log->issueKey()][$dayNo].= sprintf(
                PHP_EOL . '%s %s',
                $log->timeSpent(),
                $this->shortenWorklogComment($log->comment())
            );
            $rows[$log->issueKey()][$dayNo] = trim($rows[$log->issueKey()][$dayNo]);
            if (!isset($rows['Sum'][$dayNo])) {
                $rows['Sum'][$dayNo] = 0;
            }
            $rows['Sum'][$dayNo]+= $log->timeSpentSeconds();
        }

        // sum logged / max seconds
        $sum = $rows['Sum'];
        unset($rows['Sum']);
        ksort($rows);
        /** @var DateHelper $dateHelper */
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
        $table->render();
    }

    private function renderDay(OutputInterface $output, WorklogCollection $logs)
    {
        /** @var DateHelper $dateHelper */
        $dateHelper = $this->getService('technodelight.jira.date_helper');

        $rows = [];
        $totalTimes = [];
        $issues = [];
        foreach ($logs as $log) {
            if (!isset($rows[$log->issueKey()])) {
                $rows[$log->issueKey()] = [];
            }
            if (!isset($totalTimes[$log->issueKey()])) {
                $totalTimes[$log->issueKey()] = $dateHelper->humanToSeconds($log->timeSpent());
                $issues[$log->issueKey()] = $log->issue();
            } else {
                $totalTimes[$log->issueKey()]+= $dateHelper->humanToSeconds($log->timeSpent());
            }
            $rows[$log->issueKey()][] = ['worklogId' => $log->id(), 'comment' => $log->comment(), 'timeSpent' => $log->timeSpent()];
        }

        $output->writeln('');
        foreach ($rows as $issueKey => $records) {
            /** @var Issue $issue */
            $issue = $issues[$issueKey];
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
