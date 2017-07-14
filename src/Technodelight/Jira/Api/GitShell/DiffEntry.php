<?php

namespace Technodelight\Jira\Api\GitShell;

class DiffEntry
{
    private $state;
    private $file;

    private function __construct()
    {
    }

    public static function fromString($diffLine)
    {
        $diffEntry = new self;
        if (preg_match('~([A-Z]{1})\s*([^\s]+)~', $diffLine, $matches)) {
            $diffEntry->state = $matches[1];
            $diffEntry->file = $matches[2];
        }

        return $diffEntry;
    }

    public function state()
    {
        return $this->state;
    }

    public function file()
    {
        return $this->file;
    }
}
