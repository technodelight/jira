<?php

namespace Technodelight\Jira\Api;

use Technodelight\Jira\Helper\DateHelper;

class Worklog
{
    private $issueKey, $worklogId, $author, $comment, $date, $timeSpent, $timeSpentSeconds;

    private function __construct($issueKey, $worklogId, $author, $comment, $date, $timeSpent, $timeSpentSeconds)
    {
        $this->issueKey = $issueKey;
        $this->worklogId = $worklogId;
        $this->author = $author;
        $this->comment = $comment;
        $this->date = $date;
        $this->timeSpent = $timeSpent;
        $this->timeSpentSeconds = $timeSpentSeconds;
    }

    /**
     * @param array $record
     * @param string $issueKey
     * @return Worklog
     */
    public static function fromArray(array $record, $issueKey)
    {
        return new self(
            $issueKey,
            $record['id'],
            $record['author']['displayName'],
            isset($record['comment']) ? $record['comment'] : null,
            DateHelper::dateTimeFromJira($record['started'])->format('Y-m-d'),
            $record['timeSpent'],
            $record['timeSpentSeconds']
        );
    }

    public function issueKey()
    {
        return $this->issueKey;
    }

    public function id()
    {
        return $this->worklogId;
    }

    public function author()
    {
        return $this->author;
    }

    public function comment()
    {
        return $this->comment;
    }

    public function date()
    {
        return $this->date;
    }

    public function timeSpent()
    {
        return $this->timeSpent;
    }

    public function timeSpentSeconds()
    {
        return $this->timeSpentSeconds;
    }
}
