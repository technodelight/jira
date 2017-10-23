<?php

namespace Technodelight\Jira\Domain;

use Iterator;
use Countable;

class IssueCollection implements Iterator, Countable
{
    private $startAt, $maxResults, $total, $issues = [];

    public function __construct($startAt, $maxResults, $total, array $issues)
    {
        $this->startAt = $startAt;
        $this->maxResults = $maxResults;
        $this->total = $total;
        foreach ($issues as $issue) {
            $this->issues[] = Issue::fromArray($issue);
        }
    }

    public static function fromSearchArray(array $resultArray)
    {
        return new self(
            $resultArray['startAt'],
            $resultArray['maxResults'],
            $resultArray['total'],
            $resultArray['issues']
        );
    }

    public function count()
    {
        return count($this->issues);
    }

    /**
     * @return Issue
     */
    public function current()
    {
        return current($this->issues);
    }

    /**
     * @return Issue|false
     */
    public function next()
    {
        return next($this->issues);
    }

    /**
     * @return int|null
     */
    public function key()
    {
        return key($this->issues);
    }

    public function rewind()
    {
        reset($this->issues);
    }

    public function valid()
    {
        $item = current($this->issues);
        return $item !== false;
    }

    public function keys()
    {
        $keys = [];
        foreach ($this as $issue) {
            $keys[] = $issue->issueKey();
        }
        return $keys;
    }

    public function merge(IssueCollection $collection)
    {
        foreach ($collection as $issue) {
            $this->add($issue);
        }
    }

    public function add(Issue $issue)
    {
        if (!$this->findById($issue->id())) {
            $this->issues[] = $issue;
        }
    }

    public function sort(callable $callable)
    {
        uasort($this->issues, $callable);
    }

    public function find($issueKey)
    {
        foreach ($this as $issue) {
            if ($issue->issueKey() == $issueKey) {
                return $issue;
            }
        }
    }

    public function findById($id)
    {
        foreach ($this as $issue) {
            if ($issue->id() == $id) {
                return $issue;
            }
        }
    }

    public function findByIndex($index)
    {
        foreach ($this as $idx => $issue) {
            if ($idx == $index) {
                return $issue;
            }
        }
    }
}
