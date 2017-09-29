<?php


namespace Technodelight\Jira\Renderer\Issue;

use Hoa\Stream\IStream\Out;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Comment as IssueComment;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\ColorExtractor;
use Technodelight\Jira\Helper\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Renderer;

class Comment implements Renderer
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;
    /**
     * @var \Technodelight\Jira\Helper\ColorExtractor
     */
    private $colorExtractor;

    public function __construct(TemplateHelper $templateHelper, ColorExtractor $colorExtractor)
    {
        $this->templateHelper = $templateHelper;
        $this->colorExtractor = $colorExtractor;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        if ($comments = $issue->comments()) {
            $output->writeln($this->tab('<comment>comments:</comment>'));
            $output->writeln($this->tab($this->tab($this->renderComments($output, $comments))));
        }
    }

    /**
     * @param IssueComment[] $comments
     */
    public function renderComments(OutputInterface $output, array $comments)
    {
        $self = $this;
        return array_map(
            function(IssueComment $comment) use ($output, $self) {
                return $self->renderComment($output, $comment);
            },
            $comments
        );
    }

    public function renderComment(OutputInterface $output, IssueComment $comment)
    {
        return "<info>{$comment->author()->name()}</info> ({$comment->created()->format('Y-m-d H:i:s')}): <fg=black>({$comment->id()})</>" . PHP_EOL
            . $this->tab($this->tab(wordwrap($this->renderTags($output, trim($comment->body())))));
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
}
