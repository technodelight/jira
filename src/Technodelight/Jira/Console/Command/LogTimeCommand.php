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
                'Issue key, like PROJ-123 OR worklog ID'
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
                'Date to put your log to, like \'yesterday 12:00\' or \'' . date('Y-m-d') . '\''
            )
            ->addOption(
                'delete',
                'd',
                InputOption::VALUE_NONE,
                'Delete worklog'
            )
            ->addOption(
                'move',
                'm',
                InputOption::VALUE_REQUIRED,
                'Move worklog to another date'
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getService('console.dialog_helper');
        $gitHelper = $this->getService('technodelight.jira.git_helper');
        $templateHelper = $this->getService('technodelight.jira.template_helper');
        $jira = $this->getService('technodelight.jira.api');

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

        if ($input->getOption('delete') || $input->getOption('move')) {
            return;
        }

        $worklog = false;
        if (intval($issueKey)) {
            $worklog = $jira->retrieveWorklogs([$issueKey])->current();
        }

        if (!$input->getArgument('time')) {
            $timeSpent = $dialog->askAndValidate(
                $output,
                ($worklog ? "You logged '{$worklog->timeSpent()}' previously. Leave the time empty to keep this value." : '')
                . PHP_EOL . '<comment>Please enter the time you want to log against <info>' . $issueKey .'</info>:</> ',
                function ($answer) {
                    if (!preg_match('~^[0-9hmd. ]+$~', $answer)) {
                        throw new \RuntimeException(
                            "It's not possible to log '$answer' as time, as it's not matching the allowed format (numbers, dot and h/m/d as hours/minutes/days)."
                        );
                    }

                    return $answer;
                },
                false,
                $worklog ? $worklog->timeSpent() : '1d'
            );

            $input->setArgument('time', $timeSpent);
        }

        if (!$input->getArgument('comment')) {
            $commitMessagesSummary = '';
            $defaultMessage = null;
            if ($commitMessages = $this->retrieveGitCommitMessages($issueKey)) {
                $commitMessagesSummary = PHP_EOL . '<comment>What you have done so far: (based on your git commit messages):</>' . PHP_EOL
                    . str_repeat(' ', 2) . $templateHelper->tabulate(wordwrap(implode(PHP_EOL, $commitMessages)), 2) . PHP_EOL . PHP_EOL;
                if (count($commitMessages) == 1) {
                    $defaultMessage = trim(reset($commitMessages), '- ');
                }
            }

            $comment = $dialog->ask(
                $output,
                PHP_EOL . "<comment>Do you want to add a comment on your work log?</>" . PHP_EOL
                . $commitMessagesSummary
                . "If you leave the comment empty, the description will " .
                    ($worklog ? "be left as '{$worklog->comment()}'" : "be set to '$defaultMessage'")
                . PHP_EOL . PHP_EOL
                . '<comment>Comment:</> ',
                false
            );
            $comment = trim($comment);

            $input->setArgument('comment', $comment ?: $defaultMessage);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jira = $this->getService('technodelight.jira.api');
        $dateHelper = $this->getService('technodelight.jira.date_helper');

        $issueKey = $this->issueKeyArgument($input);
        $timeSpent = $input->getArgument('time') ?: null;
        $comment = $input->getArgument('comment') ?: null;
        $startDay = $input->getOption('move');
        if (!$startDay) {
            $startDay = $this->dateArgument($input);
        }

        if (intval($issueKey)) {
            try {
                if ($input->getOption('delete')) {
                    $this->deleteWorklog($issueKey);
                    $output->writeln(
                        sprintf('<comment>Worklog <info>%d</info> has been deleted successfully</comment>', $issueKey)
                    );
                } else {
                    $this->updateWorklog($issueKey, $timeSpent, $comment, $startDay);
                    $output->writeln(
                        sprintf('<comment>Worklog <info>%d</info> has been updated</comment>', $issueKey)
                    );
                }
            } catch (\UnexpectedValueException $exc) {
                $output->writeln($exc->getMessage());
            } catch (\Exception $exc) {
                $output->writeln(
                    sprintf('<error>Something bad happened</error>', $issueKey)
                );
                $output->writeln(sprintf('<error>%s</error>', $exc->getMessage()));
            }
        } else {
            if (!$timeSpent) {
                return $output->writeln('<error>You need to specify the issue and time arguments at least</error>');
            }

            $template = Simplate::fromFile($this->getApplication()->directory('views') . '/Commands/logtime.template');
            $output->writeln(
                $this->deDoubleNewlineize(
                    $template->render($this->logNewWork($issueKey, $timeSpent, $comment, $startDay))
                )
            );
        }
    }

    private function deleteWorklog($worklogId)
    {
        $jira = $this->getService('technodelight.jira.api');
        if ($worklog = $jira->retrieveWorklogs([$worklogId])->current()) {
            $jira->deleteWorklog($worklog);
            return true;
        } else {
            throw new \UnexpectedValueException(sprintf('Cannot delete worklog <info>%d</info>, it may have been deleted already.', $worklogId));
        }
    }

    private function updateWorklog($worklogId, $timeSpent, $comment, $startDay)
    {
        $jira = $this->getService('technodelight.jira.api');

        if ($worklog = $jira->retrieveWorklogs([$worklogId])->current()) {
            $updatedWorklog = clone $worklog;
            if ($timeSpent) {
                $updatedWorklog->timeSpent($timeSpent);
            }
            if ($comment) {
                $updatedWorklog->comment($comment);
            }
            if ($startDay) {
                $updatedWorklog->date($startDay);
            }

            if (!$worklog->isSame($updatedWorklog)) {
                $jira->updateWorklog($updatedWorklog);
                return true;
            } else {
                throw new \UnexpectedValueException(sprintf('Cannot update worklog <info>%d</info> as it looks like exactly the previous one.', $worklogId));
            }
        } else {
            throw new \UnexpectedValueException(sprintf('Cannot update worklog <info>%d</info>, it may have been deleted.', $worklogId));
        }
    }

    private function logNewWork($issueKey, $timeSpent, $comment, $startDay)
    {
        $jira = $this->getService('technodelight.jira.api');
        $dateHelper = $this->getService('technodelight.jira.date_helper');
        $worklog = $jira->worklog(
            $issueKey,
            $timeSpent,
            $comment ?: sprintf('Worked on issue %s', $issueKey),
            $startDay
        );

        $issue = $jira->retrieveIssue($issueKey);

        return [
            'issueKey' => $issue->issueKey(),
            'worklogId' => $worklog->id(),
            'issueUrl' => $issue->url(),
            'logged' => $timeSpent,
            'startDay' => date('Y-m-d H:i:s', strtotime($startDay)),
            'estimate' => $dateHelper->secondsToHuman($issue->estimate()),
            'spent' => $dateHelper->secondsToHuman($issue->timeSpent()),
            'worklogs' => $this->renderWorklogs($jira->retrieveIssueWorklogs($issueKey, 10)),
        ];
    }

    private function renderWorklogs($worklogs)
    {
        return $this->getService('technodelight.jira.worklog_renderer')->renderWorklogs($worklogs);
    }

    private function retrieveInProgressIssues()
    {
        $project = $this->getService('technodelight.jira.config')->project();
        return array_map(
            function (Issue $issue) {
                return $issue->issueKey();
            },
            $this->getService('technodelight.jira.api')->inprogressIssues($project, true)
        );
    }

    private function retrieveGitCommitMessages($issueKey)
    {
        $messages = array_filter(
            $this->getService('technodelight.jira.git_helper')->commitEntries(),
            function (array $entry) use ($issueKey) {
                return strpos($entry['message'], $issueKey) !== false;
            }
        );
        return array_map(
            function (array $entry) use ($issueKey) {
                return ucfirst(preg_replace('~^\s*'.preg_quote($issueKey).'\s+~', '- ', $entry['message']));
            },
            $messages
        );
    }

    private function deDoubleNewlineize($string)
    {
        return str_replace(PHP_EOL . PHP_EOL, PHP_EOL, $string);
    }
}
