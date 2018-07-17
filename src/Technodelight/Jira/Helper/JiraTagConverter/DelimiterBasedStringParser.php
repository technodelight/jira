<?php

namespace Technodelight\Jira\Helper\JiraTagConverter;

class DelimiterBasedStringParser
{
    private $startDelimiter;
    private $endDelimiter;

    public function __construct($startDelimiter, $endDelimiter)
    {

        $this->startDelimiter = $startDelimiter;
        $this->endDelimiter = $endDelimiter;
    }

    public function parse($string)
    {
        // code block
        $startCode = false;
        $buffer = '';
        $collected = [];
        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $startPeek = substr($string, $i, strlen($this->startDelimiter));
            $endPeek = substr($string, $i, strlen($this->endDelimiter));
            if ($startPeek == $this->startDelimiter && !$startCode) {
                $startCode = true;
                $buffer = $startPeek;
                $i+= (strlen($this->startDelimiter) - 1);
            } else if ($startCode && $endPeek == $this->endDelimiter) {
                $startCode = false;
                $buffer.= $startPeek;
                $collected[] = $buffer;
                $i+= (strlen($this->endDelimiter) - 1);
            } else if ($startCode) {
                $buffer.= $char;
            }
        }
        return $collected;
    }
}
