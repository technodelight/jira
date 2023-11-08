<?php

namespace Technodelight\Jira\Console\Input\Issue\Comment;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Technodelight\CliEditorInput\CliEditorInput as EditApp;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Api\JiraRestApi\SearchQuery\Builder;
use Technodelight\Jira\Connector\HoaConsole\Aggregate;
use Technodelight\Jira\Connector\HoaConsole\IssueAttachmentAutocomplete;
use Technodelight\Jira\Connector\HoaConsole\IssueAutocomplete;
use Technodelight\Jira\Connector\HoaConsole\UsernameAutocomplete;
use Technodelight\Jira\Connector\HoaConsole\Word;
use Technodelight\Jira\Domain\Comment\CommentId;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;

class Comment
{
    private Api $jira;
    private EditApp $editor;

    public function __construct(Api $jira, EditApp $editor)
    {
        $this->jira = $jira;
        $this->editor = $editor;
    }

    public function updateComment(IssueKey $issueKey, CommentId $commentId, OutputInterface $output): string
    {
        $output->write('</>');

        return $this->editor->edit(
            sprintf('Edit comment #%s on %s', $commentId, $issueKey),
            $this->jira->retrieveComment($issueKey, $commentId)->body()
        );
    }

    public function createComment(Issue $issue, InputInterface $input,  OutputInterface $output)
    {
        $autocompleter = $this->buildAutocompleters(
            $issue,
            $this->fetchPossibleIssuesCollection(),
            $this->fetchWordsList($issue)
        );
        $q = new QuestionHelper();
        $question = new Question('<info>Comment:</> ' . PHP_EOL);
        $question->setAutocompleterCallback($autocompleter);
        $question->setMultiline(true);

        return $q->ask($input, $output, $question);
    }

    private function buildAutocompleters(Issue $issue, IssueCollection $issues = null, array $words = []): Aggregate
    {
        $autocompleters = [
            $words ? new Word(array_unique($words)) : null,
            new UsernameAutocomplete($issue, $this->jira),
            !is_null($issues) ? new IssueAutocomplete($issues) : null,
            new IssueAttachmentAutocomplete($this->jira, $issue->key())
        ];

        return new Aggregate(array_filter($autocompleters));
    }

    private function fetchPossibleIssuesCollection(): IssueCollection
    {
        return $this->jira->search(
            Builder::factory()
                ->issueKeyInHistory()
                ->assemble()
        );
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
        foreach ($texts as $text) {
            foreach ($this->collectAutocompletableWords($text) as $word) {
                $words[] = $word;
            }
        }

        return array_unique($words);
    }

    private function collectAutocompletableWords(string $text): array
    {
        $text = preg_replace('~(\[\^)([^]]+)(\])~mu', '', $text);
        $text = preg_replace('~!([^|!]+)(\|thumbnail)?!~', '', $text);
        $text = preg_replace('~[^a-zA-Z0-9\s\']+~', '', $text);

        $words = array_map(static function(string $word) {
            return strtolower(trim($word));
        }, preg_split('~\s+~', $text));

        return array_filter($words, static function(string $word) {
            return mb_strlen($word) > 2;
        });
    }
}
