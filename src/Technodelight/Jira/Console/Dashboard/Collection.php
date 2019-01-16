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

    /**
     * @return DateTime
     * @throws \Exception
     */
    public function start()
    {
        return new DateTime($this->startDate);
    }

    /**
     * @return DateTime
     * @throws \Exception
     */
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

    /**
     * @param bool $onlyWorkDays return workdays only
     * @return DateTime[]
     */
    public function fromToDateRange($onlyWorkDays = false)
    {
        $dates = [];
        $current = clone $this->from;
        while ($current <= $this->to) {
            if ((in_array($current->format('N'), $this->workDays) && $onlyWorkDays) || $onlyWorkDays === false) {
                $dates[] = clone $current;
            }
            $current->modify('+1 day');
        }
        return $dates;
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
     * @return Collection[]
     * @throws \Exception
     */
    public function splitToWeeks()
    {
        $weeks = [];
        foreach ($this as $date => $worklogCollection) {
            $week = $date->format('W');
            if (!isset($weeks[$week])) {
                $weeks[$week] = new self(
                    WorklogCollection::createEmpty(),
                    new DateTime(sprintf('%sW%s last monday', $date->format('Y'), $week)),
                    new DateTime(sprintf('%sW%s sunday', $date->format('Y'), $week)),
                    $this->workDays
                );
            }
            /** @var Collection $currentWeek */
            $currentWeek = $weeks[$week];
            $currentWeek->collection->merge($worklogCollection);
        }
        foreach ($weeks as $dashCollection) {
            $dashCollection->startDate = $this->findDate($dashCollection->collection, true)->format(self::DATE_FORMAT);
            $dashCollection->endDate = $this->findDate($dashCollection->collection, false)->format(self::DATE_FORMAT);
            $dashCollection->currentDate = $dashCollection->startDate;
        }

        return $weeks;
    }

    /**
     * @param DateTime $findDate
     * @return WorklogCollection
     */
    public function findMatchingLogsForDate(DateTime $findDate)
    {
        $matchingLogs = WorklogCollection::createEmpty();
        foreach ($this->collection as $worklog) {
            /** @var $worklog Worklog */
            if ($worklog->date()->format(self::DATE_FORMAT) == $findDate->format(self::DATE_FORMAT)) {
                $matchingLogs->push($worklog);
            }
        }
        return $matchingLogs;
    }

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

    public function next()
    {
        $this->currentDate = date(
            self::DATE_FORMAT,
            strtotime('+1 day', strtotime($this->currentDate))
        );
        //TODO: check why this was added as a while loop?
//        do {
//            $this->currentDate = date(
//                self::DATE_FORMAT,
//                strtotime('+1 day', strtotime($this->currentDate))
//            );
//        } while(!in_array(date('N', strtotime($this->currentDate)), [1,2,3,4,5,6,7]));
    }

    /**
     * @return DateTime|mixed|null
     * @throws \Exception
     */
    public function key()
    {
        if ($this->currentDate <= $this->endDate) {
            return new DateTime($this->currentDate);
        }
        return null;
    }

    public function valid()
    {
        return $this->currentDate <= $this->endDate;
    }

    public function rewind()
    {
        $this->currentDate = $this->startDate;
    }

    public function count()
    {
        return $this->collection->count();
    }

    public function issuesCount()
    {
        return count($this->collection->issueKeys());
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
