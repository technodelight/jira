<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\DateHelper;
use Technodelight\Jira\Domain\Comment as IssueComment;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\Image;
use Technodelight\JiraTagConverter\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Helper\Wordwrap;
use Technodelight\Jira\Renderer\IssueRenderer;
use Technodelight\TimeAgo;

class Comment implements IssueRenderer
{
    /**
     * @var TemplateHelper
     */
    private $templateHelper;
    /**
     * @var Image
     */
    private $imageRenderer;
    /**
     * @var Wordwrap
     */
    private $wordwrap;
    /**
     * @var DateHelper
     */
    private $dateHelper;
    /**
     * @var JiraTagConverter
     */
    private $tagConverter;
    /**
     * @var bool
     */
    private $verbose;

    public function __construct(
        TemplateHelper $templateHelper,
        Image $imageRenderer,
        Wordwrap $wordwrap,
        DateHelper $dateHelper,
        JiraTagConverter $tagConverter,
        $verbose = true
    )
    {
        $this->templateHelper = $templateHelper;
        $this->imageRenderer = $imageRenderer;
        $this->wordwrap = $wordwrap;
        $this->dateHelper = $dateHelper;
        $this->tagConverter = $tagConverter;
        $this->verbose = $verbose;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        if ($comments = $this->filterComments($issue->comments())) {
            $output->writeln($this->tab('<comment>comments:</comment>'));
            $output->writeln($this->tab($this->tab($this->renderComments($output, $comments, $issue))));
        }
    }

    /**
     * @param IssueComment[] $comments
     */
    public function renderComments(OutputInterface $output, array $comments, Issue $issue = null)
    {
        $self = $this;
        return array_map(
            function(IssueComment $comment) use ($output, $self, $issue) {
                return $self->renderComment($output, $comment, $issue);
            },
            $comments
        );
    }

    public function renderComment(OutputInterface $output, IssueComment $comment, Issue $issue = null)
    {
        $content = $this->renderTags($output, trim($comment->body()));
        if ($issue) {
            $content = $this->imageRenderer->render($content, $issue);
        }

        return <<<EOL
<info>{$comment->author()->displayName()}</info> <comment>[~{$comment->author()->name()}]</>{$this->visibility($comment)} {$this->ago($comment->created())}: <fg=black>({$comment->id()}) ({$comment->created()->format('Y-m-d H:i:s')}) {$this->commentUrl($comment, $issue)}</>
{$this->tab($this->wordwrap->wrap($content))}
EOL;
    }

    private function renderTags($output, $body)
    {
        return $this->tagConverter->convert($output, $body);
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }

    /**
     * @param \Technodelight\Jira\Domain\Comment $comment
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @return string
     */
    protected function commentUrl(IssueComment $comment, Issue $issue = null)
    {
        if ($issue) {
            return $issue->url() . '#comment-' . $comment->id();
        }
        return '';
    }

    private function ago(\DateTime $date)
    {
        return TimeAgo::fromDateTime($date)->inWords();
    }

    private function visibility(IssueComment $comment)
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
    private function filterComments($comments)
    {
        if ($this->verbose) {
            return $comments;
        }

        return array_filter(
            $comments,
            function (IssueComment $comment) {
                return $comment->created() >= new \DateTime('-2 weeks');
            }
        );
    }
}
