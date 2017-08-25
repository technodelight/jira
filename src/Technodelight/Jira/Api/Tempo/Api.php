<?php

namespace Technodelight\Jira\Api\Tempo;

use Technodelight\Jira\Api\Worklog;
use Technodelight\Jira\Api\WorklogCollection;

class Api
{
    const TEMPO_DATETIME_FORMAT = 'Y-m-d\TH:i:s.U';
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function findWorklogs($dateFrom, $dateTo)
    {
        $worklogs = $this->client->get('/worklogs', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo]);
        $collection = WorklogCollection::createEmpty();
        foreach ($worklogs as $worklog) {
            $collection->push(
                Worklog::fromArray([
                    'id' => $worklog['id'],
                    'author' => $worklog['author'],
                    'comment' => isset($worklog['comment']) ? $worklog['comment'] : null,
                    'started' => $this->convertDateFormat($worklog['dateStarted']),
                    'timeSpentSeconds' => $worklog['timeSpentSeconds'],
                ], $worklog['issue']['key'])
            );
        }
        return $collection;
    }

    /**
     * @param string $date
     * @return string
     */
    private function convertDateFormat($date)
    {
        return \DateTime::createFromFormat(self::TEMPO_DATETIME_FORMAT, $date)->format('Y-m-d H:i:s');
    }
}
