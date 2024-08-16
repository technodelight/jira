<?php

declare(strict_types=1);

namespace Technodelight\Jira\Helper;

class Wordwrap
{

    public function __construct(private readonly TerminalDimensionProvider $dimensionProvider)
    {
    }

    public function wrap($text, $width = null): string
    {
        $termWidth = $width ?? ($this->dimensionProvider->width() ?: 80);
        $padding = ceil($termWidth * 0.1);

        return wordwrap($text, $termWidth - $padding);
    }

    public function shorten($text, $length = 20): string
    {
        $wrapped = explode(PHP_EOL, wordwrap($text, $length));
        $firstLine = array_shift($wrapped);

        return substr($firstLine, 0, $length - 2) . (count($wrapped) >= 1 ? '..' : '');
    }
}
