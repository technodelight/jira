<?php

namespace Technodelight\Jira\Console\Input\Worklog;

use Hoa\Console\Readline\Autocompleter\Aggregate;
use Hoa\Console\Readline\Autocompleter\Autocompleter;
use Hoa\Console\Readline\Autocompleter\Word;
use Hoa\Console\Readline\Readline;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\GitShell\Api as Git;
use Technodelight\GitShell\Branch;
use Technodelight\GitShell\LogEntry;
use Technodelight\GitShell\LogMessage;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Connector\HoaConsole\UsernameAutocomplete;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Console\Argument\AutocompletedInput;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\User;
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
        $defaultMessage = $this->defaultMessage($issue, $worklog);
        if ($keepDefault) {
            return $defaultMessage;
        }

        $reader = $this->buildReadline(
            $this->buildAutocompleter($issue, $this->fetchWordsList($issue)),
            $issue,
            $defaultMessage
        );

        $output->write($this->worklogCommentDialogText($defaultMessage, $worklog));
        $output->writeln($this->helpText());
        $comment = $reader->readLine();
        $output->write('</>');

        return $comment;
    }

    private function buildAutocompleter(Issue $issue, array $words = [])
    {
        $autocompleters = [
            $words ? new Word(array_unique($words)) : null,
            new UsernameAutocomplete($issue, $this->api)
        ];

        return new Aggregate(array_filter($autocompleters));
    }

    private function buildReadline(Autocompleter $autocompleter, Issue $issue, $defaultMessage)
    {
        $reader = new Readline;
        $reader->setAutocompleter($autocompleter);
        $reader->addHistory($this->worklogCommentFromGitCommits($issue->key()));
        foreach ($this->collectWorklogComments($issue, $this->api->user()) as $worklogComment) {
            $reader->addHistory($worklogComment);
        }
        $reader->addHistory($defaultMessage);

        return $reader;
    }

    private function helpText()
    {
        return '(Ctrl-A: beginning of the line, Ctrl-E: end of the line, Ctrl-B: backward one word, Ctrl-F: forward one word, Ctrl-W: delete first backward word)';
    }

    private function fetchWordsList(Issue $issue)
    {
        $list = $issue->comments();
        $list[] = $issue->description();
        $list[] = $issue->summary();

        return $this->collectWords($list);
    }

    private function collectWords(array $texts)
    {
        $words = [];
        foreach ($texts as $text) {
            $words = array_merge($words, $this->collectAutocompleteableWords($text));
        }

        return array_unique($words);
    }

    private function collectAutocompleteableWords($text)
    {
        $text = preg_replace('~[^a-zA-Z0-9\s\']+~', '', $text);

        $words = array_map(function($word) {
            return trim(strtolower($word));
        }, preg_split('~\s~', $text));

        return array_filter($words, function($word) {
            return mb_strlen($word) > 2;
        });
    }

    /**
     * @param Issue $issue
     * @param $defaultMessage
     * @return AutocompletedInput
     */
    protected function prepareAutocompleter(Issue $issue, $defaultMessage)
    {
        $user = $this->api->user();
        $history = $this->collectWorklogComments($issue, $user);
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

    private function defaultMessage(Issue $issue, Worklog $worklog = null)
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
            return $this->prepareWorklogCommentFromCommitMessages($commitMessages);
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
                return strpos((string) $entry->message(), (string) $issueKey) !== false;
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

    /**
     * @param LogMessage[] $commitMessages
     * @return string
     */
    private function prepareWorklogCommentFromCommitMessages(array $commitMessages)
    {
        return join(', ', array_filter(
            array_map([$this, 'prepareWorklogCommentCommitMessage'], $commitMessages)
        ));
    }

    /**
     * @param LogMessage $commitMessages
     * @return string
     */
    private function prepareWorklogCommentCommitMessage(LogMessage $commitMessage)
    {
        $comments = array_map(function ($line) {
            return trim($line, '- ,' . PHP_EOL);
        }, explode(PHP_EOL, $commitMessage->getBody() ?: $commitMessage->getHeader()));

        return join(', ', array_filter($comments));
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
     * @param Issue $issue
     * @param User $user
     * @return string[]
     */
    protected function collectWorklogComments(Issue $issue, User $user)
    {
        return array_filter(array_map(function (Worklog $log) use ($user) {
            if ($log->author()->key() == $user->key()) {
                return $log->comment();
            }

            return null;
        }, iterator_to_array($this->worklogHandler->findByIssue($issue))));
    }
}
