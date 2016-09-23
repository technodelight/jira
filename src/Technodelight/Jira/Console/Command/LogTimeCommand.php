<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Template\WorklogRenderer;
use Technodelight\Simplate;
use UnexpectedValueException;

class LogTimeCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('log')
            ->setDescription('Log work against issue')
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key, like PROJ-123'
            )
            ->addArgument(
                'time',
                InputArgument::OPTIONAL,
                'Time you spent with the issue, like \'1h\''
            )
            ->addArgument(
                'comment',
                InputArgument::OPTIONAL,
                'Add comment to worklog'
            )
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Day to put your log to, like \'yesterday 12:00\' or \'Y-m-d\''
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getService('console.dialog_helper');
        $gitHelper = $this->getService('technodelight.jira.git_helper');
        $templateHelper = $this->getService('technodelight.jira.template_helper');
        $jira = $this->getService('technodelight.jira.api');
        $project = $this->getService('technodelight.jira.config')->project();

        if (!$issueKey = $this->issueKeyArgument($input)) {
            $issues = $this->retrieveInProgressIssues();
            $index = $dialog->select(
                $output,
                PHP_EOL . '<comment>Choose an issue to log time to:</>',
                $issues,
                0
            );
            $issueKey = $issues[$index];
            $input->setArgument('issueKey', $issueKey);
        }

        if (!$input->getArgument('time')) {
            $timeSpent = $dialog->askAndValidate(
                $output,
                PHP_EOL . '<comment>Please enter the time you want to log against <info>' . $issueKey .'</info>:</> ',
                function ($answer) {
                    if (!preg_match('~^[0-9hmd.]+$~', $answer)) {
                        throw new \RuntimeException(
                            "It's not possible to log '$answer' as time, as it's not matching the allowed format."
                        );
                    }

                    return $answer;
                },
                false,
                '1d'
            );

            $input->setArgument('time', $timeSpent);
        }

        if (!$input->getArgument('comment')) {

            if ($commitMessages = $this->retrieveGitCommitMessages($issueKey)) {
                $commitMessagesSummary = PHP_EOL . '<comment>What you have done so far: (based on your git commit messages):</>' . PHP_EOL
                    . str_repeat(' ', 2) . $templateHelper->tabulate(wordwrap($commitMessages), 2) . PHP_EOL . PHP_EOL;
            }

            $comment = $dialog->ask(
                $output,
                PHP_EOL . "<comment>Do you want to add a comment on your work log?</>" . PHP_EOL
                . $commitMessagesSummary
                . "If you leave the comment empty, the description will still be set to the default 'Worked on issue $issueKey' message" . PHP_EOL
                . PHP_EOL
                . '<comment>Comment:</> ',
                false
            );

            $input->setArgument('comment', $comment);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jira = $this->getService('technodelight.jira.api');
        $dateHelper = $this->getService('technodelight.jira.date_helper');

        $issueKey = $this->issueKeyArgument($input);
        $timeSpent = $input->getArgument('time');
        $comment = $input->getArgument('comment') ?: sprintf('Worked on issue %s', $issueKey);
        $startDay = $input->getArgument('date') ?: 'today';

        if (!$issueKey || !$timeSpent) {
            return $output->writeln('<error>You need to specify the issue and time arguments at least</error>');
        }

        $worklog = $jira->worklog(
            $issueKey,
            $timeSpent,
            $comment,
            $this->startDayToJiraFormat($startDay)
        );

        $issue = $jira->retrieveIssue($issueKey);
        $template = Simplate::fromFile($this->getApplication()->directory('views') . '/Commands/logtime.template');
        $worklogs = $jira->retrieveIssueWorklogs($issueKey);

        $currentWorklogDetails = [
            'issueKey' => $issue->issueKey(),
            'worklogId' => $worklog->id(),
            'issueUrl' => $issue->url(),
            'logged' => $timeSpent,
            'startDay' => date('Y-m-d H:i:s', strtotime($startDay)),
            'estimate' => $dateHelper->secondsToHuman($issue->estimate()),
            'spent' => $dateHelper->secondsToHuman($issue->timeSpent()),
            'worklogs' => $this->renderWorklogs($worklogs),
        ];

        $output->writeln(
            $this->deDoubleNewlineize($template->render($currentWorklogDetails))
        );
    }

    private function renderWorklogs($worklogs)
    {
        return $this->getService('technodelight.jira.worklog_renderer')->renderWorklogs(array_slice($worklogs, -10));
    }

    private function retrieveInProgressIssues()
    {
        $project = $this->getService('technodelight.jira.config')->project();
        $issues = $this->getService('technodelight.jira.api')->inprogressIssues($project, true);
        $issueKeys = [];
        foreach ($issues as $issue) {
            $issueKeys[] = $issue->issueKey();
        }

        return $issueKeys;
    }

    private function retrieveGitCommitMessages($issueKey)
    {
        $messages = array_filter(
            $this->getService('technodelight.jira.git_helper')->commitEntries(),
            function (array $entry) use ($issueKey) {
                return strpos($entry['message'], $issueKey) !== false;
            }
        );
        return implode(
            PHP_EOL,
            array_map(
                function (array $entry) use ($issueKey) {
                    return ucfirst(preg_replace('~^\s*'.preg_quote($issueKey).'\s+~', '- ', $entry['message']));
                },
                $messages
            )
        );
    }

    private function deDoubleNewlineize($string)
    {
        return str_replace(PHP_EOL . PHP_EOL, PHP_EOL, $string);
    }

    private function startDayToJiraFormat($datetimeString)
    {
        $date = new \DateTime($datetimeString);
        if ($date->format('H:i:s') == '00:00:00') {
            $date->setTime(12, 0, 0);
        }
        return $date->format('Y-m-d\TH:i:s.000O');
    }
}
