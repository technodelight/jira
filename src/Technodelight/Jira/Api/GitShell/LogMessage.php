<?php

namespace Technodelight\Jira\Api\GitShell;

class LogMessage
{
    private $header;
    private $body;

    public static function fromString($string)
    {
        $instance = new self;
        list ($header, $message) = $instance->parse($string);
        $instance->header = $header;
        $instance->body = $message;
        return $instance;
    }

    public function hasBody()
    {
        return !empty($this->body);
    }

    public function hasHeader()
    {
        return !empty($this->header);
    }

    public function getBody()
    {
        return trim($this->body);
    }

    public function getHeader()
    {
        return trim($this->header);
    }

    public function __toString()
    {
        return trim($this->getHeader() . PHP_EOL . PHP_EOL . PHP_EOL . $this->getBody(), PHP_EOL);
    }

    private function parse($string)
    {
        $lines = array_map('trim', explode(PHP_EOL, $string));
        if (count($lines) == 1) { //it's only a header
            return [$lines[0], ''];
        }
        if (count($lines) >= 3) { // header w/ body
            return [$lines[0], join(PHP_EOL, array_slice($lines, 1))];
        }

        // something else
        return ['', join(PHP_EOL, $lines)];
    }
}
