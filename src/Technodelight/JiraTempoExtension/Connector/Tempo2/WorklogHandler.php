<?php

namespace Technodelight\JiraTempoExtension\Connector\Tempo2;

use DateTime;
use ICanBoogie\Storage\Storage;
use Technodelight\Tempo2\Api;
use Technodelight\Jira\Connector\WorklogHandler as WorklogHandlerInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Domain\WorklogCollection;
use Technodelight\Jira\Helper\DateHelper;

class WorklogHandler implements WorklogHandlerInterface
{
    const DATETIME_FORMAT = 'Y-m-d';
    /**
     * @var Api
     */
    private $api;
    /**
     * @var \ICanBoogie\Storage\Storage
     */
    private $storage;

    public function __construct(Api $api, Storage $storage)
    {
        $this->api = $api;
        $this->storage = $storage;
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @return WorklogCollection
     * @throws \Exception
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
     * @param null $limit
     * @return WorklogCollection
     * @throws \Exception
     */
    public function findByIssue(Issue $issue, $limit = null)
    {
        $worklogs = $this->api->findByIssue((string) $issue->issueKey());

        $collection = WorklogCollection::createEmpty();
        foreach ($worklogs as $worklog) {
            $collection->push($this->worklogFromTempoArray($worklog));
        }
        return $collection;
    }

    /**
     * @param int $worklogId
     * @return Worklog
     * @throws \Exception
     */
    public function retrieve($worklogId)
    {
        $response = $this->api->retrieve($worklogId);
        return $this->worklogFromTempoArray($response);
    }

    /**
     * @param Worklog $worklog
     * @return Worklog
     * @throws \Exception
     */
    public function create(Worklog $worklog)
    {
        $response = $this->api->create(
            (string) $worklog->issueKey(),
            $worklog->author()->id(),
            $worklog->date()->format(Api::TEMPO_DATETIME_FORMAT),
            $worklog->timeSpentSeconds(),
            $worklog->comment()
        );
        $this->storage->clear();
        return $this->worklogFromTempoArray($response);
    }

    /**
     * @param Worklog $worklog
     * @return Worklog
     * @throws \Exception
     */
    public function update(Worklog $worklog)
    {
        $response = $this->api->update(
            $worklog->id(),
            $worklog->date()->format(Api::TEMPO_DATETIME_FORMAT),
            $worklog->timeSpentSeconds(),
            $worklog->comment()
        );
        $this->storage->clear();
        return $this->worklogFromTempoArray($response);
    }

    /**
     * @param \Technodelight\Jira\Domain\Worklog $worklog
     * @return bool
     */
    public function delete(Worklog $worklog)
    {
        $this->storage->clear();
        return (bool) $this->api->delete($worklog->id());
    }

    /**
     * @param array $worklog
     * @return Worklog
     * @throws \Exception
     */
    private function worklogFromTempoArray(array $worklog)
    {
        /*
            self: required (string)
            tempoWorklogId: required (integer)
            jiraWorklogId: (integer)
            issue: required (Issue)
            self: required (string)
            key: required (string)
            timeSpentSeconds: required (number)
            startDate: required (date-only)
            startTime: required (time-only)
            description: required (string)
            createdAt: required (datetime)
            updatedAt: required (datetime)
            author: required (User)
            self: required (string)
            username: required (string)
            displayName: required (string)
            attributes: (attributes)
            self: required (string)
            values: required (array of Work Attribute Value)
            Items: Work Attribute Value
         */
        return Worklog::fromArray([
            'id' => $worklog['tempoWorklogId'],
            'author' => [
                'accountId' => isset($worklog['author']['accountId']) ? $worklog['author']['accountId'] : null,
                'key' => isset($worklog['author']['username']) ? $worklog['author']['username'] : null,
                'name' => isset($worklog['author']['username']) ? $worklog['author']['username'] : null,
                'displayName' => !empty($worklog['author']['displayName']) ? $worklog['author']['displayName'] : 'unknown',
            ],
            'comment' => isset($worklog['description']) ? $worklog['description'] : null,
            'started' => $this->convertDateFormat($worklog['startDate'], $worklog['startTime']),
            'timeSpentSeconds' => $worklog['timeSpentSeconds'],
        ], $worklog['issue']['key']);
    }

    /**
     * @param string $date
     * @param string $time
     * @return string
     * @throws \Exception
     */
    private function convertDateFormat($date, $time)
    {
        return (new DateTime($date . ' ' . $time))->format(DateHelper::FORMAT_FROM_JIRA);
    }
}
