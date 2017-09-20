<?php

namespace Technodelight\Jira\Console\Command;

use Hoa\Console\Readline\Autocompleter\Word;
use Hoa\Console\Readline\Readline;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\GitShell\LogEntry;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Simplate;

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
        /** @var \Symfony\Component\Console\Helper\DialogHelper $dialog */
        $dialog = $this->getService('console.dialog_helper');
        /** @var \Technodelight\Jira\Connector\WorklogHandler $worklogHandler */
        $worklogHandler = $this->getService('technodelight.jira.worklog_handler');

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
        if (intval($issueKey)) { // updating a worklog
            /** @var Worklog $worklog */
            $worklog = $worklogHandler->retrieve($issueKey);
            $issueKey = $worklog->issueKey();
        }

        if (!$input->getArgument('time')) {
            /** @var DateHelper $dateHelper */
            $dateHelper = $this->getService('technodelight.jira.date_helper');

            $timeSpent = $dialog->askAndValidate(
                $output,
                $this->loggedTimeDialogText($worklog, $issueKey),
                function ($answer) {
                    if (!preg_match('~^[0-9hmd. ]+$~', $answer)) {
                        throw new \RuntimeException(
                            "It's not possible to log '$answer' as time, as it's not matching the allowed format (numbers, dot and h/m/d as hours/minutes/days)."
                        );
                    }

                    return $answer;
                },
                false,
                $worklog ? $dateHelper->secondsToHuman($worklog->timeSpentSeconds()) : '1d'
            );

            $input->setArgument('time', $timeSpent);
        }

        if (!$input->getArgument('comment')) {
            $defaultMessage = $this->worklogCommentFromGitCommits($issueKey);
            $comment = $this->getWorklogCommentWithAutocomplete($output, $defaultMessage, $worklog, $issueKey);

            $input->setArgument('comment', $comment ?: $defaultMessage);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKeyOrWorklogId = $this->issueKeyArgument($input);
        $timeSpent = $input->getArgument('time') ?: null;
        $comment = $input->getArgument('comment') ?: null;
        $worklogDate = $input->getOption('move');

        if (intval($issueKeyOrWorklogId)) {
            try {
                if ($input->getOption('delete')) {
                    $this->deleteWorklog($issueKeyOrWorklogId);
                    $output->writeln(
                        sprintf('<comment>Worklog <info>%d</info> has been deleted successfully</comment>', $issueKeyOrWorklogId)
                    );
                } else {
                    $this->updateWorklog($issueKeyOrWorklogId, $timeSpent, $comment, $worklogDate);
                    $output->writeln(
                        sprintf('<comment>Worklog <info>%d</info> has been updated</comment>', $issueKeyOrWorklogId)
                    );
                }
            } catch (\UnexpectedValueException $exc) {
                $output->writeln($exc->getMessage());
                return 1;
            } catch (\Exception $exc) {
                $output->writeln(
                    sprintf('<error>Something bad happened</error>', $issueKeyOrWorklogId)
                );
                $output->writeln(sprintf('<error>%s</error>', $exc->getMessage()));
                return 1;
            }
        } else {
            if (!$timeSpent) {
                $output->writeln('<error>You need to specify the issue and time arguments at least</error>');
                return 1;
            }

            $template = Simplate::fromFile($this->getApplication()->directory('views') . '/Commands/logtime.template');
            $output->writeln(
                $this->deDoubleNewlineize(
                    $template->render($this->logNewWork($issueKeyOrWorklogId, $timeSpent, $comment, $this->dateArgument($input)))
                )
            );
        }

        return 0;
    }

    private function deleteWorklog($worklogId)
    {
        $handler = $this->worklogHandler();
        if ($worklog = $handler->retrieve($worklogId)) {
            $handler->delete($worklog);
            return true;
        } else {
            throw new \UnexpectedValueException(
                sprintf('Cannot delete worklog <info>%d</info>, it may have been deleted already or does not exists.', $worklogId)
            );
        }
    }

    private function updateWorklog($worklogId, $timeSpent, $comment, $startDay)
    {
        $handler = $this->worklogHandler();
        $dateHelper = $this->dateHelper();

        /** @var Worklog $worklog */
        if ($worklog = $handler->retrieve($worklogId)) {
            $updatedWorklog = clone $worklog;
            if ($timeSpent) {
                $updatedWorklog->timeSpentSeconds($dateHelper->humanToSeconds($timeSpent));
            }
            if ($comment) {
                $updatedWorklog->comment($comment);
            }
            if ($startDay) {
                $updatedWorklog->date($startDay);
            }

            if (!$worklog->isSame($updatedWorklog)) {
                $handler->update($worklog);
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
        $dateHelper = $this->dateHelper();
        $user = $this->jiraApi()->user();

        $worklog = $this->worklogHandler()->create(
            Worklog::fromArray([
                'id' => null,
                'author' => $user,
                'comment' => $comment,
                'started' => date('Y-m-d H:i:s', strtotime($startDay)),
                'timeSpentSeconds' => $this->dateHelper()->humanToSeconds($timeSpent)
            ], $issueKey)
        );
        $issue = $this->jiraApi()->retrieveIssue($issueKey);

//        $worklog = $jira->worklog(
//            $issueKey,
//            $timeSpent,
//            $comment ?: sprintf('Worked on issue %s', $issueKey),
//            $startDay
//        );

        return [
            'issueKey' => $issue->issueKey(),
            'worklogId' => $worklog->id(),
            'issueUrl' => $issue->url(),
            'logged' => $timeSpent,
            'startDay' => date('Y-m-d H:i:s', strtotime($startDay)),
            'estimate' => $dateHelper->secondsToHuman($issue->estimate()),
            'spent' => $dateHelper->secondsToHuman($issue->timeSpent()),
            'worklogs' => $this->renderWorklogs($this->jiraApi()->retrieveIssueWorklogs($issueKey, 10)),
        ];
    }

    private function renderWorklogs($worklogs)
    {
        return $this->getService('technodelight.jira.worklog_renderer')->renderWorklogs($worklogs);
    }

    private function retrieveInProgressIssues()
    {
        return array_map(
            function (Issue $issue) {
                return $issue->issueKey();
            },
            iterator_to_array($this->jiraApi()->inprogressIssues())
        );
    }

    private function retrieveGitCommitMessages($issueKey)
    {
        $messages = array_filter(
            iterator_to_array($this->gitLog()),
            function (LogEntry $entry) use ($issueKey) {
                return strpos($entry->message(), $issueKey) !== false;
            }
        );

        return array_map(
            function (LogEntry $entry) use ($issueKey) {
                return ucfirst(preg_replace('~^\s*'.preg_quote($issueKey).'\s+~', ', ', $entry->message()));
            },
            $messages
        );
    }

    private function gitLog()
    {
        if ($parent = $this->gitHelper()->parentBranch()) {
            return $this->gitHelper()->log($parent);
        }

        return [];
    }

    private function deDoubleNewlineize($string)
    {
        return str_replace(PHP_EOL . PHP_EOL, PHP_EOL, $string);
    }

    /**
     * @param Worklog $worklog
     * @param string|int $issueKeyOrWorklogId
     * @return string
     */
    protected function loggedTimeDialogText($worklog, $issueKeyOrWorklogId)
    {
        if ($worklog) {
            $confirm = sprintf(
                "You logged '%s' previously. Leave the time empty to keep this value.",
                $this->dateHelper()->secondsToHuman($worklog->timeSpentSeconds())
            );
        } else {
            $confirm = '';
        }

        return $confirm . PHP_EOL
            . sprintf('<comment>Please enter the time you want to log against <info>%s</info>:</> ', $issueKeyOrWorklogId);
    }

    /**
     * @param Worklog $worklog
     * @param string|null $defaultMessage
     * @return string
     */
    private function worklogCommentDialogText($worklog, $defaultMessage)
    {
        $verb = $worklog ? 'left as' : 'set to';
        $comment = $worklog ? $worklog->comment() : $defaultMessage;
return <<<EOL
<comment>Do you want to add a comment on your work log?</>

If you leave the comment empty, the description will be $verb:

<info>$comment</>

<comment>Comment:</> 
EOL;
    }

    private function worklogCommentFromGitCommits($issueKey)
    {
        $defaultMessage = null;
        if ($commitMessages = $this->retrieveGitCommitMessages($issueKey)) {
            $messages = [];
            foreach ($commitMessages as $message) {
                $messages[] = trim($message, '- ,');
            }
            $defaultMessage = join(', ', $messages);
        }
        return $defaultMessage;
    }

    private function getWorklogCommentWithAutocomplete(OutputInterface $output, $defaultMessage, $worklog, $issueKey)
    {
        $issue = $this->jiraApi()->retrieveIssue($issueKey);
        $output->writeln($this->worklogCommentDialogText($worklog, $defaultMessage));
        $readline = new Readline;
        $readline->setAutocompleter(new Word($this->getAutocompleteWords($issue, $defaultMessage)));
        $comment = $readline->readLine();
        $output->write('</>');
        return $comment;
    }

    private function getAutocompleteWords(Issue $issue, $defaultMessage)
    {
        $words = array_map(
            function($string) {
                return trim(trim($string, '-,'.PHP_EOL));
            },
            explode(' ', $defaultMessage . ' ' . $issue->description() . $issue->summary())
        );
        return array_unique(array_filter($words));
    }

    /**
     * @return \Technodelight\Jira\Connector\WorklogHandler
     */
    private function worklogHandler()
    {
        return $this->getService('technodelight.jira.worklog_handler');
    }

    /**
     * @return DateHelper
     */
    private function dateHelper()
    {
        return $this->getService('technodelight.jira.date_helper');
    }

    /**
     * @return Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }

    /**
     * @return \Technodelight\Jira\Api\GitShell\Api
     */
    private function gitHelper()
    {
        return $this->getService('technodelight.gitshell.api');
    }
}
