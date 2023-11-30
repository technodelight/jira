<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Input\Issue\Comment;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\CliEditorInput\CliEditorInput as EditApp;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Api\JiraRestApi\SearchQuery\Builder;
use Technodelight\Jira\Connector\Autocompleter\Factory;
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
    private const AUTOCOMPLETE_WORD_MIN_LENGTH = 2;

    public function __construct(
        private readonly Api $jira,
        private readonly EditApp $editor,
        private readonly Factory $autocompleterFactory
    ) {
    }

    public function updateComment(IssueKey $issueKey, CommentId $commentId, OutputInterface $output): string
    {
        $output->write('</>');

        return $this->editor->edit(
            sprintf('Edit comment #%s on %s', $commentId, $issueKey),
            $this->jira->retrieveComment($issueKey, $commentId)->body()
        );
    }

    public function createComment(Issue $issue, InputInterface $input,  OutputInterface $output): string
    {
        $autocomplete = $this->autocompleterFactory->create($input, $output);
        $autocomplete->setAutocomplete($this->buildAutocompleteAggregate(
            $issue,
            $this->fetchPossibleIssuesCollection(),
            $this->fetchWordsList($issue)
        ));
        $output->writeln('<info>Comment:</>');

        return $autocomplete->read();
    }

    private function buildAutocompleteAggregate(
        Issue $issue,
        IssueCollection $issues = null,
        array $words = []
    ): Aggregate {
        return new Aggregate(array_filter([
            $words ? new Word(array_unique($words)) : null,
            new UsernameAutocomplete($issue, $this->jira),
            !is_null($issues) ? new IssueAutocomplete($issues) : null,
            new IssueAttachmentAutocomplete($this->jira, $issue->key())
        ]));
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
        $list = [];
        foreach ($issue->comments() as $comment) {
            $list[] = $comment->body();
        }
        $list[] = $issue->description() ?? '';
        $list[] = $issue->summary() ?? '';

        return $this->collectWords($list);
    }

    private function collectWords(array $texts): array
    {
        $words = [];
        foreach (array_filter($texts) as $text) {
            foreach ($this->sanitize($text) as $word) {
                $words[] = $word;
            }
        }

        return array_unique($words);
    }

    private function sanitize(string $text): array
    {
        // sanitize text from special jira tags and non-alphabetic chars
        $text = preg_replace(
            [
                '~(\[\^)([^]]+)(\])~mu',
                '~!([^|!]+)(\|thumbnail)?!~',
                '~[^a-zA-Z0-9\s\']+~'
            ],
            '',
            $text
        );

        // filter out everything what's less than the defined minimum length
        return array_filter(preg_split('~\s+~', $text), static function(string $word) {
            return mb_strlen(trim($word)) > self::AUTOCOMPLETE_WORD_MIN_LENGTH;
        });
    }
}
