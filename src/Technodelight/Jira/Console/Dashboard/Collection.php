<?php

namespace Technodelight\Jira\Console\Dashboard;

use Countable;
use DateTime;
use Iterator;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\WorklogCollection;

class Collection implements Iterator, Countable
{
    const DATE_FORMAT = 'Y-m-d';

    private $collection;
    private $startDate;
    private $endDate;
    private $currentDate;
    private $from;
    private $to;
    private $workDays;
    private $days;

    public static function fromWorklogCollection(WorklogCollection $collection, DateTime $from, DateTime $to, array $workDays = [1,2,3,4,5])
    {
        return new self($collection, $from, $to, $workDays);
    }

    public function start()
    {
        return new DateTime($this->startDate);
    }

    public function end()
    {
        return new DateTime($this->endDate);
    }

    public function from()
    {
        return $this->from;
    }

    public function to()
    {
        return $this->to;
    }

    public function daysRange()
    {
        return $this->to()->diff($this->from())->format('%a') + 1;
    }

    public function days()
    {
        if (!isset($this->days)) {
            $this->days = 0;
            $date = clone $this->from();
            while ($date->format(self::DATE_FORMAT) <= $this->to()->format(self::DATE_FORMAT)) {
                if (in_array($date->format('N'), $this->workDays)) {
                    $this->days++;
                }
                $date->modify('+1 day');
            }
        }
        return $this->days;
    }

    public function isADay()
    {
        return $this->days() == 1;
    }

    public function isAWeek()
    {
        return $this->daysRange() >= 5 && $this->daysRange() <= 7;
    }

    public function isAMonth()
    {
        return $this->daysRange() >= 28 && $this->daysRange() <= 31;
    }

    /**
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        $matchingLogs = WorklogCollection::createEmpty();
        foreach ($this->collection as $worklog) {
            /** @var $worklog Worklog */
            if ($worklog->date()->format(self::DATE_FORMAT) == $this->currentDate) {
                $matchingLogs->push($worklog);
            }
        }
        return $matchingLogs;
    }

    /**
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        do {
            $this->currentDate = date(
                self::DATE_FORMAT,
                strtotime('+1 day', strtotime($this->currentDate))
            );
        } while(!in_array(date('N', strtotime($this->currentDate)), [1,2,3,4,5]));
    }

    /**
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        if ($this->currentDate <= $this->endDate) {
            return new DateTime($this->currentDate);
        }
        return null;
    }

    /**
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->currentDate <= $this->endDate;
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->currentDate = $this->startDate;
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
        return $this->collection->count();
    }

    public function totalTimeSpentSeconds()
    {
        return $this->collection->totalTimeSpentSeconds();
    }

    private function findDate(WorklogCollection $collection, $min)
    {
        $current = false;
        foreach ($collection as $worklog) {
            /** @var $worklog Worklog */
            if (!$current) {
                $current = $worklog->date();
            } else {
                $current = $min ? min($current, $worklog->date()) : max($current, $worklog->date());
            }
        }
        return $current ? $current : new DateTime();
    }

    private function __construct(WorklogCollection $collection, DateTime $from, DateTime $to, array $workDays)
    {
        $this->collection = $collection;
        $this->startDate = $this->findDate($collection, true)->format(self::DATE_FORMAT);
        $this->endDate = $this->findDate($collection, false)->format(self::DATE_FORMAT);
        $this->from = $from;
        $this->to = $to;
        $this->currentDate = $this->startDate;
        $this->workDays = $workDays;
    }
}
