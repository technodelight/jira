<?php

namespace Technodelight\Jira\Api\EditApp\Driver;

use Technodelight\Jira\Api\EditApp\Driver;
use Technodelight\ShellExec\Command;
use Technodelight\ShellExec\Shell;

class Vim implements Driver
{
    /**
     * @var \Technodelight\ShellExec\Shell
     */
    private $shell;

    public function __construct(Shell $shell)
    {
        $this->shell = $shell;
    }

    /**
     * @param string $title
     * @param string $content
     * @return string
     */
    public function edit($title, $content)
    {
        $filename = $this->filenameFromTitle($title);
        file_put_contents(
            $this->filenameFromTitle($title),
            $this->prepareContent($title, $content)
        );
        $this->shell->exec(
            Command::create('vim')
                ->withArgument($filename)
                ->withStdErrTo('/dev/null')
        );
        $editedContent = $this->fetchEditedContentWithoutComments($filename);
        unlink($filename);
        return $editedContent;
    }

    private function filenameFromTitle($title)
    {
        return preg_replace(
            '/[\x00-\x08\x0b-\x1f\x7f]/',
            '',
            str_replace(
                array('\\', '/', '?', ':', '*', '"', '>', '<', '|', ' ', '\'', '&'),
                '-',
                iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title)
            )
        );
    }

    private function prepareContent($title, $content)
    {
        return '# ' . $title . PHP_EOL . '#' . PHP_EOL . $content;
    }

    /**
     * @param string $filename
     * @return string
     */
    private function fetchEditedContentWithoutComments($filename)
    {
        return join(PHP_EOL, array_filter(
            file($filename, FILE_IGNORE_NEW_LINES),
            function ($row) {
                return strpos($row, '#') !== 0;
            }
        ));
    }
}
