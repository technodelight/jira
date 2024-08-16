<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\IssueStats;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class Stats implements IteratorAggregate, Countable
{
    private array $stats = [];

    /**
     * @param string $issueKey
     * @param Event[] $events
     */
    public function collectEvents(string $issueKey, array $events): void
    {
        $lastTime = 0;
        $viewCount = array_filter(
            $events,
            function (Event $event) {
                return $event->isType(Event::VIEW);
            }
        );
        $updateCount = array_filter(
            $events,
            function (Event $event) {
                return $event->isType(Event::UPDATE);
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

    public function orderByMostRecent(): Stats
    {
        $this->orderBy('time', '>');
        return $this;
    }

    public function issueKeys(?int $limit = null): array
    {
        if ($limit === null) {
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
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->stats);
    }

    private function orderBy(string $field, string $operation): void
    {
        uasort($this->stats, function ($elemA, $elemB) use($field, $operation) {
            if ($elemA[$field] == $elemB[$field]) {
                return 0;
            }
            if ($operation === '<') {
                return $elemA[$field] < $elemB[$field] ? -1 : 1;
            }

            return $elemA[$field] > $elemB[$field] ? -1 : 1;
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
    public function count(): int
    {
        return count($this->stats);
    }
}
