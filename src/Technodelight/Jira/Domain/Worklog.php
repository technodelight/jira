<?php

namespace Technodelight\Jira\Domain;

use Technodelight\Jira\Helper\DateHelper;
use Technodelight\SecondsToNone;

class Worklog
{
    private $issueKey, $worklogId, $author, $comment, $date, $timeSpentSeconds, $issue = null;

    private function __construct($issueKey, $worklogId, $author, $comment, $date, $timeSpentSeconds)
    {
        $this->issueKey = $issueKey;
        $this->worklogId = $worklogId;
        if (!empty($author) && is_array($author)) {
            $this->author = User::fromArray($author);
        } else {
            $this->author = $author;
        }
        $this->comment = $comment;
        $this->date = $date;
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
            $record['author'],
            isset($record['comment']) ? $record['comment'] : null,
            $record['started'],
            $record['timeSpentSeconds']
        );
    }

    public static function fromIssueAndArray(Issue $issue, array $record)
    {
        $worklog = self::fromArray($record, $issue->key());
        $worklog->issue = $issue;
        return $worklog;
    }

    public function issueKey()
    {
        return $this->issueKey;
    }

    /**
     * @return \Technodelight\Jira\Domain\Issue
     */
    public function issue()
    {
        return $this->issue;
    }

    public function assignIssue(Issue $issue)
    {
        if (!empty($this->issueKey) && $issue->issueKey() != $this->issueKey) {
            throw new \UnexpectedValueException(
                'Unable to assign issue'
            );
        }
        $this->issue = $issue;
        $issue->worklogs()->push($this);
    }

    public function id()
    {
        return $this->worklogId;
    }

    /**
     * @return User
     */
    public function author()
    {
        return $this->author;
    }

    /**
     * @param string|null $comment
     * @return string|$this
     */
    public function comment($comment = null)
    {
        if ($comment) {
            $this->comment = $comment;
            return $this;
        }
        return $this->comment;
    }

    /**
     * @param string|null $date
     * @return \Datetime
     */
    public function date($date = null)
    {
        if ($date) {
            $this->date = $date;
        }

        return \DateTime::createFromFormat(DateHelper::FORMAT_FROM_JIRA, $this->date);
    }

    /**
     * @param string|null $timeSpent set time spent using human text (1d2h)
     * @deprecated
     * @return string|$this
     */
    public function timeSpent($timeSpent = null)
    {
        if ($timeSpent) {
            $this->timeSpentSeconds = (new SecondsToNone())->humanToSeconds($timeSpent);
            return $this;
        }
        return (new SecondsToNone)->secondsToHuman($this->timeSpentSeconds);
    }

    /**
     * @param int|null $seconds
     * @return int
     */
    public function timeSpentSeconds($seconds = null)
    {
        if (!is_null($seconds)) {
            $this->timeSpentSeconds = $seconds;
        }
        return $this->timeSpentSeconds;
    }

    public function isSame(Worklog $log)
    {
        return [$log->timeSpentSeconds, $log->comment, $log->date, $log->author()]
            == [$this->timeSpentSeconds, $this->comment, $this->date, $this->author()];
    }
}
