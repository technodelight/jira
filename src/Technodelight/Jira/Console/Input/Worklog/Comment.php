<?php

namespace Technodelight\Jira\Console\Input\Worklog;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Technodelight\GitShell\ApiInterface as Git;
use Technodelight\GitShell\Branch;
use Technodelight\GitShell\LogEntry;
use Technodelight\GitShell\LogMessage;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Connector\HoaConsole\Aggregate;
use Technodelight\Jira\Connector\HoaConsole\UsernameAutocomplete;
use Technodelight\Jira\Connector\HoaConsole\Word;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\User;
use Technodelight\Jira\Domain\Worklog;

class Comment
{
    /**
     * @var Api
     */
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function read(InputInterface$input, OutputInterface $output, Issue $issue = null, Worklog $worklog = null, $keepDefault = false)
    {
        $q = new QuestionHelper();
        $question = new Question($this->worklogCommentDialogText($worklog->comment(), $worklog));
        $question->setAutocompleterCallback(new Aggregate([
            new Word($this->fetchWordsList($issue)),
            new UsernameAutocomplete($issue, $this->api)
        ]));

        return $q->ask($input, $output, $question);
    }

    private function buildAutocompleter(Issue $issue, array $words = [])
    {
        $autocompleters = [
            $words ? new Word(array_unique($words)) : null,
            new UsernameAutocomplete($issue, $this->api)
        ];

        return new Aggregate(array_filter($autocompleters));
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
            foreach ($this->collectAutocompleteableWords($text) as $word) {
                $words[] = $word;
            }
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
}
