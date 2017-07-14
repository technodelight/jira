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
            /** @var Comment $comment */
            $contents[] = $template->render(
                [
                    'author' => $comment->author(),
                    'body' => $this->templateHelper->tabulate(wordwrap($this->renderTags($comment->body())), 8),
                    'created' => $comment->created()->format('Y-m-d H:i:s'),
                ]
            );
        }

        return implode(PHP_EOL, $contents);
    }

    private function renderTags($body)
    {
        if ($numOfMatches = preg_match_all('~({code})(.*)({code})~smu', $body, $matches)) {
            for ($i = 0; $i < $numOfMatches; $i++) {
                $body = str_replace(
                    $matches[1][$i].$matches[2][$i].$matches[3][$i],
                    '<fg=yellow>'.$matches[2][$i].'</>',
                    $body
                );
            }
        }
        if ($numOfMatches = preg_match_all('~(\*)([^*]+)(\*)~smu', $body, $matches)) {
            for ($i = 0; $i < $numOfMatches; $i++) {
                $body = str_replace(
                    $matches[1][$i].$matches[2][$i].$matches[3][$i],
                    '<fg=cyan>'.$matches[2][$i].'</>',
                    $body
                );
            }
        }
        if ($numOfMatches = preg_match_all('~({color[^}]*})([^*]+)({color})~smu', $body, $matches)) {
            for ($i = 0; $i < $numOfMatches; $i++) {
                $body = str_replace(
                    $matches[1][$i].$matches[2][$i].$matches[3][$i],
                    $matches[2][$i],
                    $body
                );
            }
        }
        if ($numOfMatches = preg_match_all('~(\[\~)([^]]+)(\])~smu', $body, $matches)) {
            for ($i = 0; $i < $numOfMatches; $i++) {
                $body = str_replace(
                    $matches[1][$i].$matches[2][$i].$matches[3][$i],
                    '<fg=cyan>' . $matches[2][$i] . '</>',
                    $body
                );
            }
        }

        $body = str_replace('{panel}', '', $body);

        return $body;
    }
}
