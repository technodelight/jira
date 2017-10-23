<?php

namespace Technodelight\Jira\Api\GitShell;

class LogEntry
{
    const DATE_FORMAT = 'Y-m-d H:i:s';

    private $hash;
    private $message;
    private $authorName;
    private $authorDate;

    public static function fromArray(array $params)
    {
        $entry = new self;
        $entry->hash = $params['hash'];
        $entry->message = $params['message'];
        $entry->authorName = $params['authorName'];
        $entry->authorDate = \DateTime::createFromFormat(self::DATE_FORMAT, $params['authorDate']);
        return $entry;
    }

    public function hash()
    {
        return $this->hash;
    }

    public function message()
    {
        return LogMessage::fromString($this->message);
    }

    public function authorName()
    {
        return $this->authorName;
    }

    public function authorDate()
    {
        return $this->authorDate;
    }
}
