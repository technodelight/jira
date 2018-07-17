<?php

namespace Technodelight\Jira\Console\Input\Worklog;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\GitShell\Api as Git;
use Technodelight\GitShell\Branch;
use Technodelight\GitShell\LogEntry;
use Technodelight\GitShell\LogMessage;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Console\Argument\AutocompletedInput;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;

class Comment
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Git
     */
    private $git;
    /**
     * @var WorklogHandler
     */
    private $worklogHandler;

    public function __construct(Api $api, Git $git, WorklogHandler $worklogHandler)
    {
        $this->api = $api;
        $this->git = $git;
        $this->worklogHandler = $worklogHandler;
    }

    public function read(OutputInterface $output, Issue $issue = null, Worklog $worklog = null, $keepDefault = false)
    {
        $defaultMessage = $this->defaultMessage($worklog, $issue);
        if ($keepDefault) {
            return $defaultMessage;
        }

        $input = $this->prepareAutocompleter($issue, $defaultMessage);
        $output->write($this->worklogCommentDialogText($defaultMessage, $worklog));
        $output->writeln($input->helpText());
        $comment = $input->getValue();
        $output->write('</>');
        return $comment;
    }

    /**
     * @param Issue $issue
     * @param $defaultMessage
     * @return AutocompletedInput
     */
    protected function prepareAutocompleter(Issue $issue, $defaultMessage)
    {
        $user = $this->api->user();
        $history = array_filter(array_map(function (Worklog $log) use ($user) {
            if ($log->author()->key() == $user->key()) {
                return $log->comment();
            }

            return null;
        }, iterator_to_array($this->worklogHandler->findByIssue($issue))));
        $history[] = $this->worklogCommentFromGitCommits($issue->key());
        $history[] = $defaultMessage;

        return new AutocompletedInput(
            $this->api,
            $issue,
            null,
            [
                $defaultMessage,
                $issue->description(),
                $issue->summary()
            ],
            array_unique($history)
        );
    }

    private function defaultMessage(Worklog $worklog = null, Issue $issue)
    {
        if (!is_null($worklog)) {
            return null; // force comment to be the existing comment when editing
        }

        return $this->worklogCommentFromGitCommits($issue->key());
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
     * @param string $issueKey
     * @return LogMessage[]
     */
    private function retrieveGitCommitMessages($issueKey)
    {
        $messages = array_filter(
            $this->gitLog($issueKey),
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

    private function gitLog($issueKey)
    {
        $branches = array_filter(
            $this->git->branches($issueKey),
            function(Branch $branch) {
                return $branch->current() || !$branch->isRemote();
            }
        );

        /** @var Branch $branch */
        $branch = end($branches);
        if (empty($branch)) {
            return [];
        }

        if ($parent = $this->git->parentBranch()) {
            return iterator_to_array($this->git->log($parent, $branch->name()));
        }

        return [];
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
}
