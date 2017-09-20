<?php

namespace Technodelight\Jira\Template;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Comment;
use Technodelight\Jira\Console\Application;
use Technodelight\Jira\Helper\ColorExtractor;
use Technodelight\Jira\Helper\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Simplate;

class CommentRenderer
{
    /**
     * @var TemplateHelper
     */
    private $templateHelper;

    /**
     * @var string
     */
    private $viewsDir;

    /**
     * @var FormatterHelper
     */
    private $formatterHelper;

    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var \Technodelight\Jira\Helper\ColorExtractor
     */
    private $colorExtractor;

    public function __construct(Application $app, TemplateHelper $templateHelper, FormatterHelper $formatterHelper, ColorExtractor $colorExtractor)
    {
        $this->viewsDir = $app->directory('views');
        $this->templateHelper = $templateHelper;
        $this->formatterHelper = $formatterHelper;
        $this->output = new NullOutput;
        $this->colorExtractor = $colorExtractor;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param  Comment[]  $comments
     *
     * @return string
     */
    public function renderComments(array $comments)
    {
        $template = Simplate::fromFile($this->viewsDir . DIRECTORY_SEPARATOR . 'Commands/comment.template');
        $contents = [];
        foreach ($comments as $comment) {
            /** @var Comment $comment */
            $contents[] = $template->render(
                [
                    'author' => $comment->author(),
                    'body' => $this->templateHelper->tabulate(wordwrap($this->renderTags($comment->body())), 8),
                    'created' => $comment->created()->format('Y-m-d H:i:s'),
                    'commentId' => $comment->id()
                ]
            );
        }

        return implode(PHP_EOL, $contents);
    }

    private function renderTags($body)
    {
        $tagRenderer = new JiraTagConverter($this->output, $this->colorExtractor);
        return $tagRenderer->convert($body);
    }
}
