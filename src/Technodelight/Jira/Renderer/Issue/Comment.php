<?php


namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Comment as IssueComment;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\ColorExtractor;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Helper\Image;
use Technodelight\Jira\Helper\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Helper\Wordwrap;
use Technodelight\Jira\Renderer\IssueRenderer;
use Technodelight\TimeAgo;

class Comment implements IssueRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;
    /**
     * @var \Technodelight\Jira\Helper\ColorExtractor
     */
    private $colorExtractor;
    /**
     * @var \Technodelight\Jira\Helper\Image
     */
    private $imageRenderer;
    /**
     * @var \Technodelight\Jira\Helper\Wordwrap
     */
    private $wordwrap;
    /**
     * @var \Technodelight\Jira\Helper\DateHelper
     */
    private $dateHelper;

    public function __construct(TemplateHelper $templateHelper, ColorExtractor $colorExtractor, Image $imageRenderer, Wordwrap $wordwrap, DateHelper $dateHelper)
    {
        $this->templateHelper = $templateHelper;
        $this->colorExtractor = $colorExtractor;
        $this->imageRenderer = $imageRenderer;
        $this->wordwrap = $wordwrap;
        $this->dateHelper = $dateHelper;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        if ($comments = $issue->comments()) {
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
<info>{$comment->author()->displayName()}</info> <comment>[~{$comment->author()->name()}]</> {$this->ago($comment->created())}: <fg=black>({$comment->id()}) ({$comment->created()->format('Y-m-d H:i:s')}) {$this->commentUrl($comment, $issue)}</>
{$this->tab($this->wordwrap->wrap($content))}
EOL;
    }

    private function renderTags($output, $body)
    {
        $tagRenderer = new JiraTagConverter($output, $this->colorExtractor);
        return $tagRenderer->convert($body);
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
}
