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
use Technodelight\Jira\Console\Argument\AutocompletedInput;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Helper\AutocompleteHelper;
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
                'issueKeyOrWorklogId',
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
        $dialog = $this->dialogHelper();

        if (!$issueKeyOrWorklogId = $input->getArgument('issueKeyOrWorklogId')) {
            $issues = $this->retrieveInProgressIssues();
            $index = $dialog->select(
                $output,
                PHP_EOL . '<comment>Choose an issue to log time to:</>',
                $issues,
                0
            );
            $issueKey = $issues[$index];
            $input->setArgument('issueKeyOrWorklogId', $issueKey);
        }

        if ($input->getOption('delete') || $input->getOption('move')) {
            return;
        }

        if (intval($issueKeyOrWorklogId)) { // updating a worklog
            /** @var Worklog $worklog */
            $worklog = $this->worklogHandler()->retrieve($issueKeyOrWorklogId);
            $issueKey = $worklog->issueKey();
        } else { // creating a new worklog
            $worklog = false;
            $issueKey = $issueKeyOrWorklogId;
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
            $defaultMessage = $this->worklogCommentFromGitCommits($issueKeyOrWorklogId);
            $comment = $this->getWorklogCommentWithAutocomplete($output, $defaultMessage, $worklog, $issueKeyOrWorklogId);

            $input->setArgument('comment', $comment ?: $defaultMessage);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKeyOrWorklogId = $input->getArgument('issueKeyOrWorklogId');
        $timeSpent = $input->getArgument('time') ?: null;
        $comment = $input->getArgument('comment') ?: null;
        $worklogDate = $input->getOption('move');

        if (intval($issueKeyOrWorklogId)) { // updating a worklog
            /** @var Worklog $worklog */
            $worklog = $this->worklogHandler()->retrieve($issueKeyOrWorklogId);
            $issueKey = $worklog->issueKey();
        } else { // creating a new worklog
            $worklog = false;
            $issueKey = $issueKeyOrWorklogId;
        }

        if ($worklog) {
            try {
                if ($input->getOption('delete')) {
                    $this->deleteWorklog($worklog);
                    $output->writeln(
                        sprintf('<comment>Worklog <info>%d</info> has been deleted successfully</comment>', $worklog->id())
                    );
                } else {
                    $this->updateWorklog($worklog, $timeSpent, $comment, $worklogDate);
                    $output->writeln(
                        sprintf('<comment>Worklog <info>%d</info> has been updated</comment>', $worklog->id())
                    );
                }
            } catch (\UnexpectedValueException $exc) {
                $output->writeln($exc->getMessage());
                return 1;
            } catch (\Exception $exc) {
                $output->writeln(
                    sprintf('<error>Something bad happened while processing %s</error>', $issueKeyOrWorklogId)
                );
                $output->writeln(sprintf('<error>%s</error>', $exc->getMessage()));
                return 1;
            }
        } else {
            if (!$timeSpent) {
                $output->writeln('<error>You need to specify the issue and time arguments at least</error>');
                return 1;
            }

            $worklog = $this->logNewWork($issueKey, $timeSpent, $comment, $this->dateArgument($input));
            $this->showSuccessMessages($output, $worklog);
        }

        return 0;
    }

    private function deleteWorklog(Worklog $worklog)
    {
        $this->worklogHandler()->delete($worklog);
        return true;
    }

    private function updateWorklog(Worklog $worklog, $timeSpent, $comment, $startDay)
    {
        $dateHelper = $this->dateHelper();

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
            $this->worklogHandler()->update($worklog);
            return true;
        }

        throw new \UnexpectedValueException(sprintf('Cannot update worklog <info>%d</info> as it looks the same as it was.', $worklog->id()));
    }

    private function logNewWork($issueKey, $timeSpent, $comment, $startDay)
    {
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
        $worklog->assignIssue($issue);

        return $worklog;
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

    /**
     * @param Worklog $worklog
     * @param string|int $issueKey
     * @return string
     */
    protected function loggedTimeDialogText($worklog, $issueKey)
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
            . sprintf('<comment>Please enter the time you want to log against <info>%s</info>:</> ', $issueKey . ($worklog ? ' ('.$worklog->id().')' : ''));
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
        $input = new AutocompletedInput($issue, null, [$defaultMessage, $issue->description(), $issue->summary()]);
        $comment = $input->getValue();
        $output->write('</>');
        return $comment;
    }

    private function showSuccessMessages(OutputInterface $output, Worklog $worklog)
    {
        $output->writeln(
            "You have successfully logged <comment>{$this->dateHelper()->secondsToHuman($worklog->timeSpentSeconds())}</comment>"
            ." to issue <info>{$worklog->issueKey()} on {$worklog->date()->format('Y-m-d H:i:s')}</info> ({$worklog->id()})"
        );
        $output->writeln('');
        $output->writeln(
            "Time spent: <comment>{$worklog->issue()->timeSpent()}</comment>, Remaining estimate: <comment>{$worklog->issue()->remainingEstimate()}</comment>"
        );
        $output->writeln('');
        $output->writeln('Logged work so far:');
        $this->worklogRenderer()->renderWorklogs($output, $worklog->issue()->worklogs());
    }

    /**
     * @return \Symfony\Component\Console\Helper\DialogHelper
     */
    private function dialogHelper()
    {
        return $this->getService('console.dialog_helper');
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

    /**
     * @return \Technodelight\Jira\Renderer\Issue\Worklog
     */
    private function worklogRenderer()
    {
        return $this->getService('technodelight.jira.renderer.issue.worklog');
    }
}
