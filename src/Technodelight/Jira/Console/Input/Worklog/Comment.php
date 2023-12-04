<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Input\Worklog;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Connector\HoaConsole\Aggregate;
use Technodelight\Jira\Connector\HoaConsole\UsernameAutocomplete;
use Technodelight\Jira\Connector\HoaConsole\Word;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;

class Comment
{
    public function __construct(private readonly Api $api)
    {
    }

    public function read(
        InputInterface$input,
        OutputInterface $output,
        Issue $issue = null,
        ?Worklog $worklog = null
    ): ?string {
        $q = new QuestionHelper();
        $question = new Question($this->worklogCommentDialogText($worklog ? $worklog->comment() : '', $worklog));
        $question->setAutocompleterCallback(new Aggregate([
            new Word($this->fetchWordsList($issue)),
            new UsernameAutocomplete($issue, $this->api)
        ]));

        return $q->ask($input, $output, $question);
    }

    private function fetchWordsList(Issue $issue): array
    {
        $list = $issue->comments();
        $list[] = $issue->description();
        $list[] = $issue->summary();

        return $this->collectWords($list);
    }

    private function collectWords(array $texts): array
    {
        $words = [];
        foreach (array_filter($texts) as $text) {
            foreach ($this->collectAutocompleteableWords($text) as $word) {
                $words[] = $word;
            }
        }

        return array_unique($words);
    }

    private function collectAutocompleteableWords(string $text): array
    {
        $text = preg_replace('~[^a-zA-Z0-9\s\']+~', '', $text);

        $words = array_map(function($word) {
            return trim(strtolower($word));
        }, preg_split('~\s~', $text));

        return array_filter($words, function($word) {
            return mb_strlen($word) > 2;
        });
    }

    private function worklogCommentDialogText(string $defaultMessage, ?Worklog $worklog = null): string
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
