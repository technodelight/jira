<?php

namespace Technodelight\Jira\Console\IssueStats;

use Traversable;

class Stats implements \IteratorAggregate, \Countable
{
    private $stats = [];

    /**
     * @param string $issueKey
     * @param Event[] $events
     */
    public function collectEvents($issueKey, $events)
    {
        $lastTime = 0;
        $viewCount = array_filter(
            $events,
            function (Event $event) {
                return $event->is(Event::VIEW);
            }
        );
        $updateCount = array_filter(
            $events,
            function (Event $event) {
                return $event->is(Event::UPDATE);
            }
        );
        foreach ($events as $event) {
            if ($event->time() > $lastTime) {
                $lastTime = $event->time();
            }
        }
        $this->stats[$issueKey] = [
            'total' => count($events),
            'time' => $lastTime,
            Event::VIEW => count($viewCount),
            Event::UPDATE => count($updateCount),
        ];
    }

    public function orderByTotal()
    {
        $this->orderBy('total', '<');
        return $this;
    }

    public function orderByMostRecent()
    {
        $this->orderBy('time', '>');
        return $this;
    }

    public function issueKeys($limit = null)
    {
        if (is_null($limit)) {
            return array_keys($this->stats);
        }

        $issueKeys = [];
        foreach (array_keys($this->stats) as $issueKey) {
            if (count($issueKeys) < $limit) {
                $issueKeys[] = $issueKey;
            }
        }
        return $issueKeys;
    }

    /**
     * Retrieve an external iterator
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->stats);
    }

    private function orderBy($field, $op)
    {
        uasort($this->stats, function ($a, $b) use($field, $op) {
            if ($a[$field] == $b[$field]) {
                return 0;
            }
            if ($op == '<') {
                return $a[$field] < $b[$field] ? -1 : 1;
            } else {
                return $a[$field] > $b[$field] ? -1 : 1;
            }
        });
    }

    /**
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->stats);
    }
}
