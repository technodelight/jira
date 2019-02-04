<?php

namespace Technodelight\Jira\Console\Input\Issue\Comment;

use Hoa\Console\Readline\Autocompleter\Aggregate;
use Hoa\Console\Readline\Autocompleter\Autocompleter;
use Hoa\Console\Readline\Autocompleter\Word;
use Hoa\Console\Readline\Readline;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\EditApp\EditApp;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Api\JiraRestApi\SearchQuery\Builder;
use Technodelight\Jira\Connector\HoaConsole\IssueAttachmentAutocomplete;
use Technodelight\Jira\Connector\HoaConsole\IssueAutocomplete;
use Technodelight\Jira\Connector\HoaConsole\IssueMetaAutocompleter;
use Technodelight\Jira\Connector\HoaConsole\UsernameAutocomplete;
use Technodelight\Jira\Console\Argument\CommentId;
use Technodelight\Jira\Console\Argument\IssueKey;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;

class Comment
{
    /**
     * @var Api
     */
    private $jira;
    /**
     * @var EditApp
     */
    private $editor;

    public function __construct(Api $jira, EditApp $editor)
    {
        $this->jira = $jira;
        $this->editor = $editor;
    }

    public function updateComment(IssueKey $issueKey, CommentId $commentId, OutputInterface $output)
    {
        $output->write('</>');

        return $this->editor->edit(
            sprintf('Edit comment #%d on %s', $commentId, $issueKey),
            $this->jira->retrieveComment((string) $issueKey, (string) $commentId)->body()
        );
    }

    public function createComment(Issue $issue, OutputInterface $output)
    {
        $autocompleter = $this->buildAutocompleter($issue, $this->fetchPossibleIssuesCollection(), $this->fetchWordsList($issue));
        $reader = $this->buildReadline($autocompleter);

        $output->writeln([
            '',
            '<info>Comment:</> ' . $this->autocompleterHints()
        ]);
        $output->write('</>');

        return $reader->readLine();
    }

    private function buildAutocompleter(Issue $issue, IssueCollection $issues = null, array $words = [])
    {
        $autocompleters = [
            $words ? new Word(array_unique($words)) : null,
            new UsernameAutocomplete($issue, $this->jira),
            !is_null($issues) ? new IssueAutocomplete($issues) : null,
            new IssueAttachmentAutocomplete($this->jira, $issue->key())
        ];

        return new Aggregate(array_filter($autocompleters));
    }

    private function buildReadline(Autocompleter $autocompleter)
    {
        $reader = new Readline;
        $reader->setAutocompleter($autocompleter);

        return $reader;
    }

    private function fetchPossibleIssuesCollection()
    {
        return $this->jira->search(
            Builder::factory()
                ->issueKeyInHistory()
                ->assemble()
        );
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
        $text = preg_replace('~(\[\^)([^]]+)(\])~smu', '', $text);
        $text = preg_replace('~!([^|!]+)(\|thumbnail)?!~', '', $text);
        $text = preg_replace('~[^a-zA-Z0-9\s\']+~', '', $text);

        $words = array_map(function($word) {
            return trim(strtolower($word));
        }, preg_split('~\s+~', $text));

        return array_filter($words, function($word) {
            return mb_strlen($word) > 2;
        });
    }

    private function autocompleterHints()
    {
        return '(Ctrl-A: beginning of the line, Ctrl-E: end of the line, Ctrl-B: backward one word, Ctrl-F: forward one word, Ctrl-W: delete first backward word)';
    }
}
