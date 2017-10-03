<?php

namespace Technodelight\Jira\Connector\Tempo;

use DateTime;
use Technodelight\Jira\Api\Tempo\Api;
use Technodelight\Jira\Connector\WorklogHandler as WorklogHandlerInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\WorklogCollection;

class WorklogHandler implements WorklogHandlerInterface
{
    const DATETIME_FORMAT = 'Y-m-d';
    /**
     * @var Api
     */
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @return WorklogCollection
     */
    public function find(DateTime $from, DateTime $to)
    {
        $worklogs = $this->api->find(
            $from->format(self::DATETIME_FORMAT),
            $to->format(self::DATETIME_FORMAT)
        );

        $collection = WorklogCollection::createEmpty();
        foreach ($worklogs as $worklog) {
            $collection->push(
                $this->worklogFromTempoArray($worklog)
            );
        }
        return $collection;
    }

    /**
     * @param Issue $issue
     * @return WorklogCollection
     */
    public function findByIssue(Issue $issue)
    {
        $worklogs = array_filter(
            $this->api->all(),
            function (array $worklog) use ($issue) {
                return $worklog['issue']['key'] == $issue->key();
            }
        );

        $collection = WorklogCollection::createEmpty();
        foreach ($worklogs as $worklog) {
            $collection->push($this->worklogFromTempoArray($worklog));
        }
        return $collection;
    }

    /**
     * @param int $worklogId
     * @return Worklog
     */
    public function retrieve($worklogId)
    {
        $response = $this->api->retrieve($worklogId);
        return $this->worklogFromTempoArray($response);
    }

    /**
     * @param Worklog $worklog
     * @return Worklog
     */
    public function create(Worklog $worklog)
    {
        $response = $this->api->create(
            $worklog->issueKey(),
            $worklog->author()->name(),
            $worklog->date()->format(Api::TEMPO_DATETIME_FORMAT),
            $worklog->timeSpentSeconds(),
            $worklog->comment()
        );
        return $this->worklogFromTempoArray($response);
    }

    /**
     * @param Worklog $worklog
     * @return Worklog
     */
    public function update(Worklog $worklog)
    {
        $response = $this->api->update(
            $worklog->id(),
            $worklog->date()->format(Api::TEMPO_DATETIME_FORMAT),
            $worklog->timeSpentSeconds(),
            $worklog->comment()
        );
        return $this->worklogFromTempoArray($response);
    }

    /**
     * @param \Technodelight\Jira\Domain\Worklog $worklog
     * @return bool
     */
    public function delete(Worklog $worklog)
    {
        return (bool) $this->api->delete($worklog->id());
    }

    /**
     * @param array $worklog
     * @return Worklog
     */
    private function worklogFromTempoArray(array $worklog)
    {
        return Worklog::fromArray([
            'id' => $worklog['id'],
            'author' => $worklog['author'],
            'comment' => isset($worklog['comment']) ? $worklog['comment'] : null,
            'started' => $this->convertDateFormat($worklog['dateStarted']),
            'timeSpentSeconds' => $worklog['timeSpentSeconds'],
        ], $worklog['issue']['key']);
    }

    /**
     * @param string $date
     * @return string
     */
    private function convertDateFormat($date)
    {
        return (new DateTime($date))->format('Y-m-d H:i:s');
    }
}
