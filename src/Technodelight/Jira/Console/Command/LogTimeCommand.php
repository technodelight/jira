<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Technodelight\Jira\Api\GitShell\LogEntry;
use Technodelight\Jira\Api\GitShell\LogMessage;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\AutocompletedInput;
use Technodelight\Jira\Console\Argument\IssueKey;
use Technodelight\Jira\Console\Argument\IssueKeyOrWorklogId;
use Technodelight\Jira\Console\Argument\IssueKeyOrWorklogIdResolver;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Helper\DateHelper;

class LogTimeCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('log')
            ->setDescription('Log work against issue')
            ->addArgument(
                IssueKeyOrWorklogIdResolver::NAME,
                InputArgument::OPTIONAL,
                'Issue key, like PROJ-123 OR a specific worklog\'s ID'
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
                'Date to put your log to, like \'yesterday 12:00\' or \'' . date('Y-m-d') . '\', anything http://php.net/strtotime can parse'
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
                'Move worklog to another date',
                false
            )
            ->addOption(
                'interactive',
                'I',
                InputOption::VALUE_NONE,
                'Log time interactively'
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('interactive')) {
            return;
        }

        /** @var \Technodelight\Jira\Console\Argument\IssueKeyOrWorklogId $issueKeyOrWorklogId */
        $issueKeyOrWorklogId = $this->resolveIssueKeyOrWorklogId($input);

        if ($issueKeyOrWorklogId->isEmpty()) {
            if ($issueKey = IssueKey::fromBranch($this->gitHelper()->currentBranch())) {
                $input->setArgument('issueKeyOrWorklogId', (string) $issueKey);
                $issueKeyOrWorklogId = IssueKeyOrWorklogId::fromString((string) $issueKey);
            } else {
                $issue = $this->askIssueToChooseFrom($input, $output);
                $input->setArgument('issueKeyOrWorklogId', $issue->key());
                $issueKeyOrWorklogId = IssueKeyOrWorklogId::fromString($issue->key());
            }
        }

        if ($input->getOption('delete') || $input->getOption('move')) {
            return;
        }

        if (!$input->getArgument('time')) {
            $input->setArgument('time', $this->askForTimeToLog($input, $output, $issueKeyOrWorklogId->issueKey(), $issueKeyOrWorklogId->worklog()));
        }

        if (!$input->getArgument('comment')) {
            if ($issueKeyOrWorklogId->isWorklogId()) {
                $defaultMessage = null;
            } else {
                $defaultMessage = $this->worklogCommentFromGitCommits($issueKeyOrWorklogId->issueKey());
            }
            $comment = $this->getWorklogCommentWithAutocomplete($output, $defaultMessage, $issueKeyOrWorklogId->issueKey(), $issueKeyOrWorklogId->worklog());

            $input->setArgument('comment', $comment ?: $defaultMessage);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('interactive')) {
            return $this->interactiveTimelog($input, $output);
        }

        return $this->doWorklog($input, $output);
    }

    private function doWorklog(InputInterface $input, OutputInterface $output)
    {
        $issueKeyOrWorklogId = $this->resolveIssueKeyOrWorklogId($input);
        $timeSpent = $input->getArgument('time') ?: null;
        $comment = $input->getArgument('comment') ?: null;
        $worklogDate = $input->getOption('move') ? $this->dateOption($input, 'move') : null;

        if ($issueKeyOrWorklogId->isWorklogId()) {
            try {
                if ($input->getOption('delete')) {
                    $this->deleteWorklog($issueKeyOrWorklogId->worklog());
                    $output->writeln(
                        sprintf('<comment>Worklog <info>%d</info> has been deleted successfully</comment>', $issueKeyOrWorklogId->worklog()->id())
                    );
                } else {
                    $this->updateWorklog($issueKeyOrWorklogId->worklog(), $timeSpent, $comment, $worklogDate);
                    $output->writeln(
                        sprintf('<comment>Worklog <info>%d</info> has been updated</comment>', $issueKeyOrWorklogId->worklog()->id())
                    );
                }
            } catch (\UnexpectedValueException $exception) {
                $output->writeln($exception->getMessage());
                return 1;
            } catch (\Exception $exception) {
                $output->writeln(
                    sprintf('<error>Something bad happened while processing %s</error>', $issueKeyOrWorklogId->worklogId())
                );
                $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
                return 1;
            }
        } else {
            if (!$timeSpent) {
                $output->writeln('<error>You need to specify the issue and time arguments at least</error>');
                return 1;
            }

            $worklog = $this->logNewWork(
                $issueKeyOrWorklogId->issueKey(),
                $timeSpent,
                $comment ?: 'Worked on issue ' . $issueKeyOrWorklogId->issueKey(),
                $this->dateArgument($input)
            );
            $this->showSuccessMessages($output, $worklog);
        }

        return 0;
    }

    private function interactiveTimelog(InputInterface $input, OutputInterface $output)
    {
        $worklogs = $this->worklogHandler()->find(new \DateTime, new \DateTime);
        $timeLeft = $this->dateHelper()->humanToSeconds('1d') - $worklogs->totalTimeSpentSeconds();
        if ($timeLeft <= 0) {
            $output->writeln(sprintf('<info>You already filled in your timesheets for %s</info>', $this->dateArgument($input)));
            return 1;
        }

        while ($timeLeft > 0) {
            $output->writeln(sprintf('<comment>%s</comment> time left to log.', $this->dateHelper()->secondsToHuman($timeLeft)));
            $issue = $this->askIssueToChooseFrom($input, $output);
            $time = $this->askForTimeToLog($input, $output, $issue->key());
            $defaultMessage = $this->worklogCommentFromGitCommits($issue->key());
            $comment = $this->getWorklogCommentWithAutocomplete($output, $defaultMessage, $issue->key());
            $worklog = $this->logNewWork($issue->key(), $time, $comment ?: 'Worked on issue ' . $issue->key(), $this->dateArgument($input));
            $this->showSuccessMessages($output, $worklog);
            $timeLeft = $timeLeft - $worklog->timeSpentSeconds();
        }

        $output->writeln('<info>You have filled in your timesheets completely</info>');

        $this->renderDashboard($input, $output);

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
            $updatedWorklog->timeSpentSeconds($this->dateHelper()->humanToSeconds($timeSpent));
        }
        if ($comment) {
            $updatedWorklog->comment($comment);
        }
        if ($startDay) {
            $updatedWorklog->date(date('Y-m-d H:i:s', strtotime($startDay)));
        }

        if (!$worklog->isSame($updatedWorklog)) {
            $this->worklogHandler()->update($updatedWorklog);
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
        // load issue
        $issue = $this->jiraApi()->retrieveIssue($issueKey);
        $worklog->assignIssue($issue);
        $worklogs = $this->worklogHandler()->findByIssue($issue);
        $issue->assignWorklogs($worklogs);
        return $worklog;
    }

    /**
     * @param string $issueKey
     * @return LogMessage[]
     */
    private function retrieveGitCommitMessages($issueKey)
    {
        $messages = array_filter(
            iterator_to_array($this->gitLog()),
            function (LogEntry $entry) use ($issueKey) {
                return strpos((string) $entry->message(), $issueKey) !== false;
            }
        );

        return array_map(
            function (LogEntry $entry) use ($issueKey) {
                $message = $entry->message();
                return LogMessage::fromString(
                    ucfirst(preg_replace('~^\s*'.preg_quote($issueKey).'\s+~', '', $message->getHeader())) . PHP_EOL
                    . PHP_EOL
                    . $message->getBody()
                );
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
     * @param string|int $issueKey
     * @param Worklog $worklog
     * @return string
     */
    protected function loggedTimeDialogText($issueKey, $worklog = null)
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
     * @param string $defaultMessage
     * @param Worklog|null $worklog
     * @return string
     */
    private function worklogCommentDialogText($defaultMessage, $worklog = null)
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
        if ($commitMessages = $this->retrieveGitCommitMessages($issueKey)) {
            return count($commitMessages) == 1
                ? $this->prepareWorklogCommentFromOneCommitMessage($commitMessages)
                : $this->prepareWorklogCommentFromMultipleCommitMessages($commitMessages);
        }
        return "Worked on $issueKey";
    }

    /**
     * @param array $commitMessages
     * @return string
     */
    private function prepareWorklogCommentFromOneCommitMessage(array $commitMessages)
    {
        $commitMessage = end($commitMessages);
        $comments = array_map(function ($line) {
            return trim($line, '- ,' . PHP_EOL);
        }, explode(PHP_EOL, $commitMessage->getBody() ?: $commitMessage->getHeader()));

        return join(', ', array_filter($comments));
    }

    /**
     * @param array $commitMessages
     * @return string
     */
    private function prepareWorklogCommentFromMultipleCommitMessages(array $commitMessages)
    {
        $messages = [];
        foreach ($commitMessages as $message) {
            // format the message a bit
            $comments = array_map(function ($line) {
                return trim($line, '- ,' . PHP_EOL);
            }, explode(PHP_EOL, $message->getHeader()));
            $messages[] = join(', ', array_filter($comments));
        }

        return join(', ', array_filter($messages));
    }

    private function getWorklogCommentWithAutocomplete(OutputInterface $output, $defaultMessage, $issueKey, $worklog = null)
    {
        $issue = $this->jiraApi()->retrieveIssue($issueKey);
        $output->writeln($this->worklogCommentDialogText($defaultMessage, $worklog));
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
            "Time spent: <comment>{$this->dateHelper()->secondsToHuman($worklog->issue()->timeSpent())}</comment>, "
            . "Remaining estimate: <comment>{$this->dateHelper()->secondsToHuman($worklog->issue()->remainingEstimate())}</comment>"
        );
        $output->writeln('');
        $output->writeln('Logged work so far:');
        $this->worklogRenderer()->renderWorklogs($output, $worklog->issue()->worklogs());
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return \Technodelight\Jira\Domain\Issue
     */
    private function askIssueToChooseFrom(InputInterface $input, OutputInterface $output)
    {
        return $this->issueSelector()->chooseIssue($input, $output);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $issueKey
     * @param Worklog $worklog
     * @return string
     */
    protected function askForTimeToLog(InputInterface $input, OutputInterface $output, $issueKey, Worklog $worklog = null)
    {
        $question = new Question(
            $this->loggedTimeDialogText($issueKey, $worklog), $worklog ? $this->dateHelper()->secondsToHuman($worklog->timeSpentSeconds()) : '1d');
        $question->setValidator(function ($answer) {
            return preg_replace('~[^0-9hmds. ]+~', '', $answer);
        });
        return $this->questionHelper()->ask(
            $input,
            $output,
            $question
        );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    private function renderDashboard(InputInterface $input, OutputInterface $output)
    {
        $arrayInput = new ArrayInput([
            'date' => date('Y-m-d', strtotime($this->dateArgument($input))),
        ]);
        $dashboard = $this->getApplication()->get('dashboard');
        $dashboard->execute($arrayInput, $output);
    }

    private function resolveIssueKeyOrWorklogId(InputInterface $input)
    {
        return $this->issueKeyOrWorklogIdResolver()->argument($input);
    }

    /**
     * @return IssueKeyOrWorklogIdResolver
     */
    private function issueKeyOrWorklogIdResolver()
    {
        return $this->getService('technodelight.jira.console.argument.issue_key_or_worklog_id_resolver');
    }

    /**
     * @return \Technodelight\Jira\Console\Argument\InteractiveIssueSelector
     */
    private function issueSelector()
    {
        return $this->getService('technodelight.jira.console.interactive_issue_selector');
    }

    /**
     * @return \Symfony\Component\Console\Helper\QuestionHelper
     */
    private function questionHelper()
    {
        return $this->getHelper('question');
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
