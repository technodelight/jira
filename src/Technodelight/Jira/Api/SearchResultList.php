<?php

namespace Technodelight\Jira\Api;

use Iterator;
use Countable;

class SearchResultList implements Iterator, Countable
{
    private $startAt, $maxResults, $total, $issues;

    public function __construct($startAt, $maxResults, $total, $issues)
    {
        $this->startAt = $startAt;
        $this->maxResults = $maxResults;
        $this->total = $total;
        $this->issues = $issues;
    }

    public static function fromArray($resultArray)
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

    public function current()
    {
        $item = current($this->issues);
        return Issue::fromArray($item);
    }

    public function next()
    {
        return next($this->issues);
    }

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
}
