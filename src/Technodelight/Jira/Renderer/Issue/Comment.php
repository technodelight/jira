<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Issue;

use DateTime;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Comment as IssueComment;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\AccountIdUsernameReplacer;
use Technodelight\Jira\Helper\Image;
use Technodelight\JiraTagConverter\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Helper\Wordwrap;
use Technodelight\Jira\Renderer\IssueRenderer;
use Technodelight\TimeAgo;

class Comment implements IssueRenderer
{
    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    public function __construct(
        private readonly TemplateHelper $templateHelper,
        private readonly Image $imageRenderer,
        private readonly Wordwrap $wordwrap,
        private readonly JiraTagConverter $tagConverter,
        private readonly AccountIdUsernameReplacer $replacer,
        private readonly bool $verbose = true
    ) {}

    public function render(OutputInterface $output, Issue $issue): void
    {
        $comments = $this->filterComments($issue->comments());
        if (!empty($comments)) {
            $output->writeln($this->tab('<comment>comments:</comment>'));
            $output->writeln($this->tab($this->tab($this->renderComments($output, $comments, $issue))));
        }
    }

    public function renderComments(OutputInterface $output, array $comments, Issue $issue = null): array
    {
        $self = $this;
        return array_map(
            function(IssueComment $comment) use ($output, $self, $issue) {
                return $self->renderComment($output, $comment, $issue);
            },
            $comments
        );
    }

    public function renderComment(OutputInterface $output, IssueComment $comment, Issue $issue = null): string
    {
        $content = $this->replacer->replace(trim($comment->body()));
        $content = $this->renderTags($output, $content);
        if ($issue) {
            $content = $this->imageRenderer->render($content, $issue);
        }

        return strtr(
            '<info>{author}</> <comment>[~{username}]</>{visibility} {when}: <fg=black>({id}) ({timestamp}) {url}</>' . PHP_EOL
            . '{content}',
            [
                '{author}' => $comment->author()->displayName(),
                '{username}' => $comment->author()->name(),
                '{visibility}' => $this->visibility($comment),
                '{when}' => $this->ago($comment->created()),
                '{id}' => $comment->id()->id(),
                '{timestamp}' => $comment->created()->format('Y-m-d H:i:s'),
                '{url}' => $this->commentUrl($comment, $issue),
                '{content}' => $this->tab($this->wordwrap->wrap($content)),
            ]
        );
    }

    private function renderTags($output, $body): string
    {
        return $this->tagConverter->convert($output, $body);
    }

    private function tab($string): string
    {
        return $this->templateHelper->tabulate($string);
    }

    private function commentUrl(IssueComment $comment, ?Issue $issue = null): string
    {
        if ($issue) {
            return $issue->url() . '?focusedCommentId=' . $comment->id();
        }

        return '';
    }

    private function ago(DateTime $date): string
    {
        return TimeAgo::fromDateTime($date)->inWords();
    }

    private function visibility(IssueComment $comment): string
    {
        if ($comment->visibility()) {
            return sprintf(' <fg=red>(%s only)</>', $comment->visibility());
        }
        return '';
    }

    /**
     * @param IssueComment[] $comments
     * @return array
     */
    private function filterComments(array $comments): array
    {
        if ($this->verbose) {
            return $comments;
        }

        return array_filter(
            $comments,
            function (IssueComment $comment) {
                return $comment->created() >= new DateTime('-2 weeks');
            }
        );
    }
}
