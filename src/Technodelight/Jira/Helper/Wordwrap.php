<?php

namespace Technodelight\Jira\Helper;

class Wordwrap
{
    /**
     * @var TerminalDimensionProvider
     */
    private $terminalDimensionProvider;

    public function __construct(TerminalDimensionProvider $terminalDimensionProvider)
    {
        $this->terminalDimensionProvider = $terminalDimensionProvider;
    }

    public function wrap($text, $width = null)
    {
        $termWidth = $width ?? ($this->terminalDimensionProvider->width() ?: 80);
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
