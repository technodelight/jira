<?php

namespace Technodelight\Jira\Api;

use Countable;
use Iterator;
use Technodelight\Jira\Api\Worklog;

class WorklogCollection implements Iterator, Countable
{
    private $maxResults = 0;
    private $total = 0;
    /**
     * @var Worklog[]
     */
    private $worklogs = [];

    private function __construct()
    {
    }

    public static function fromIssueArray(Issue $issue, array $worklogs)
    {
        $collection = new self;
        foreach ($worklogs as $log) {
            $collection->worklogs[] = Worklog::fromIssueAndArray($issue, $log);
        }
        $collection->maxResults = count($collection->worklogs);
        $collection->total = $collection->maxResults;
        return $collection;
    }

    public static function fromIterator(\CallbackFilterIterator $iterator)
    {
        $collection = new self;
        $collection->worklogs = iterator_to_array($iterator);
        $collection->maxResults = count($collection->worklogs);
        $collection->total = $collection->maxResults;
        return $collection;
    }

    public static function createEmpty()
    {
        return new self;
    }

    public function count()
    {
        return count($this->worklogs);
    }

    public function current()
    {
        return current($this->worklogs);
    }

    public function next()
    {
        return next($this->worklogs);
    }

    public function key()
    {
        return key($this->worklogs);
    }

    public function rewind()
    {
        reset($this->worklogs);
    }

    public function valid()
    {
        $item = current($this->worklogs);
        return $item !== false;
    }

    public function push(Worklog $worklog)
    {
        $this->worklogs[] = $worklog;
        $this->maxResults+= 1;
        $this->total+= 1;
    }

    public function merge(WorklogCollection $collection)
    {
        $this->worklogs = array_merge($this->worklogs, $collection->worklogs);
        $this->maxResults = count($this->worklogs);
        $this->total = count($this->worklogs);
    }

    public function totalTimeSpentSeconds()
    {
        $summary = 0;
        foreach ($this->worklogs as $log) {
            $summary+= $log->timeSpentSeconds();
        }
        return $summary;
    }

    public function filterByLimit($limit)
    {
        $count = 0;
        $iterator = new \CallbackFilterIterator($this, function(Worklog $log) use ($limit, $count) {
            $count++;
            return $count <= $limit;
        });
        return WorklogCollection::fromIterator($iterator);
    }

    public function filterByUser($user)
    {
        $iterator = new \CallbackFilterIterator($this, function(Worklog $log) use ($user) {
            return $log->author()->key() == $user;
        });
        return WorklogCollection::fromIterator($iterator);
    }

    public function filterByDate($from, $to)
    {
        $iterator = new \CallbackFilterIterator($this, function(Worklog $log) use ($from, $to) {
            $date = date('Y-m-d', strtotime($log->date()));
            return $date >= $from && $date <= $to;
        });
        return WorklogCollection::fromIterator($iterator);
    }

    public function filterByIssueKey($issueKey)
    {
        $iterator = new \CallbackFilterIterator($this, function(Worklog $log) use ($issueKey) {
            return $log->issueKey() == $issueKey;
        });
        return WorklogCollection::fromIterator($iterator);
    }
}
