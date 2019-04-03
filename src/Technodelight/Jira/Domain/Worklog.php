<?php

namespace Technodelight\Jira\Domain;

use DateTime;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\Issue\IssueId;
use Technodelight\Jira\Domain\Worklog\WorklogId;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\SecondsToNone;

class Worklog
{
    /**
     * @var IssueKey
     */
    private $issueKey;
    /**
     * @var WorklogId
     */
    private $worklogId;
    /**
     * @var User|null
     */
    private $author;
    /**
     * @var string
     */
    private $comment;
    /**
     * @var DateTime
     */
    private $date;
    /**
     * @var int
     */
    private $timeSpentSeconds;
    /**
     * @var Issue|null
     */
    private $issue = null;

    private function __construct($issueKeyOrId, $worklogId, $author, $comment, $date, $timeSpentSeconds)
    {
        if (is_numeric($issueKeyOrId)) {
            $this->issueId = IssueId::fromString($issueKeyOrId);
        } else if ($issueKeyOrId instanceof IssueId) {
            $this->issueId = $issueKeyOrId;
        }
        if ($issueKeyOrId instanceof IssueKey) {
            $this->issueKey = $issueKeyOrId;
        } else if (is_string($issueKeyOrId) && !isset($this->issueId)) {
            $this->issueKey = IssueKey::fromString($issueKeyOrId);
        }
        $this->worklogId = is_string($worklogId) ? WorklogId::fromString($worklogId) : $worklogId;
        if (!empty($author) && is_array($author)) {
            $this->author = User::fromArray($author);
        } else {
            $this->author = $author;
        }
        $this->comment = $comment;
        $this->date = DateTime::createFromFormat(DateHelper::FORMAT_FROM_JIRA, $date);
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

    public function issueId()
    {
        return $this->issueId;
    }

    /**
     * Can be one of: issueKey or issueId
     * @return IssueKey|IssueId
     */
    public function issueIdentifier()
    {
        return $this->issueKey ?: $this->issueId;
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
        if (!empty($this->issueKey) && ((string)$issue->issueKey() !== (string) $this->issueKey)) {
            throw new \UnexpectedValueException(
                'Unable to assign issue'
            );
        }
        $this->issue = $issue;
        $issue->worklogs()->push($this);
    }

    /**
     * @return WorklogId
     */
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
     * @param DateTime|string|null $date
     * @return DateTime
     */
    public function date($date = null)
    {
        if ($date) {
            $this->date = $date instanceof DateTime ? $date : DateTime::createFromFormat(DateHelper::FORMAT_FROM_JIRA, $date);
        }

        return $this->date;
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
