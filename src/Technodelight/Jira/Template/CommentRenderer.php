<?php

namespace Technodelight\Jira\Template;

use Technodelight\Jira\Api\Comment;
use Technodelight\Jira\Console\Application;
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

    public function __construct(Application $app, TemplateHelper $templateHelper)
    {
        $this->viewsDir = $app->directory('views');
        $this->templateHelper = $templateHelper;
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
            $contents[] = $template->render(
                [
                    'author' => $comment->author(),
                    'body' => $this->templateHelper->tabulate(wordwrap($comment->body()), 8),
                    'created' => $comment->created()->format('Y-m-d H:i:s'),
                ]
            );
        }

        return implode(PHP_EOL, $contents);
    }
}
