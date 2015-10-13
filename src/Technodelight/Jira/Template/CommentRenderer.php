<?php

namespace Technodelight\Jira\Template;

use Technodelight\Jira\Api\Comment;
use Technodelight\Jira\Helper\TemplateHelper;

class CommentRenderer
{
    /**
     * @var TemplateHelper
     */
    private $templateHelper;

    public function __construct()
    {
        $this->templateHelper = new TemplateHelper;
    }

    /**
     * @param  Comment[]  $comments
     *
     * @return string
     */
    public function renderComments(array $comments)
    {
        $template = Template::fromFile('Technodelight/Jira/Resources/views/Commands/comment.template');
        $contents = [];
        foreach ($comments as $comment) {
            $contents[] = $template->render(
                [
                    'author' => $comment->author(),
                    'body' => $this->templateHelper->tabulate(wordwrap($comment->body()), 8),
                    'created' => $comment->created(),
                ]
            );
        }

        return implode(PHP_EOL, $contents);
    }
}
