<?php

namespace Technodelight\Jira\Helper;

use Technodelight\Jira\Console\Application;

class Wordwrap
{
    /**
     * @var \Technodelight\Jira\Console\Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function wrap($text)
    {
        $termWidth = $this->application->getTerminalDimensions()[0] ?: 80;
        $padding = ceil($termWidth * 0.1);
        return wordwrap($text, $termWidth - $padding);
    }

    public function shorten($text, $length = 20, $hardWrap = true)
    {
        $wrapped = explode(PHP_EOL, wordwrap($text, $length));
        $firstLine = array_shift($wrapped);
        if ($hardWrap) {
            $firstLine = substr($firstLine, 0, $length - 2) . (count($wrapped) >= 1 ? '..' : '');
        } else {
            $firstLine.= (count($wrapped) >= 1 ? '..' : '');
        }
        return $firstLine;
    }
}
