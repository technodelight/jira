<?php

namespace Technodelight\Jira\Api\Tempo2;

use DateTime;
use UnexpectedValueException;

class Api
{
    const TEMPO_DATETIME_FORMAT = 'Y-m-d\TH:i:s.B';
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function find($dateFrom, $dateTo)
    {
        $result = $this->client->get('worklogs', ['from' => $dateFrom, 'to' => $dateTo, 'limit' => 200]);
        $worklogs = $result['results'];
        while(!empty($result['metadata']['next'])) {
            $result = $this->client->get($result['metadata']['next']);
            $worklogs = array_merge($worklogs, $result['results']);
        }

        return $worklogs;
    }

    public function all()
    {
        return $this->client->get('worklogs')['results'];
    }

    public function retrieve($worklogId)
    {
        return $this->client->get('worklogs/' . $worklogId);
    }

    public function findByIssue($issueKey)
    {
        $result = $this->client->get('worklogs/issue/' . $issueKey);
        $worklogs = $result['results'];
        while(!empty($result['metadata']['next'])) {
            $result = $this->client->get($result['metadata']['next']);
            $worklogs = array_merge($worklogs, $result['results']);
        }

        return $worklogs;
    }

    public function create($issueKey, $authorAccountId, $startedAt, $timeSpentSeconds, $description)
    {
        $startDate = new DateTime($startedAt);
        return $this->client->post('/worklogs', [
            'authorAccountId' => $authorAccountId,
            'issueKey' => $issueKey,
            'startDate' => $startDate->format('Y-m-d'),
            'startTime' => $startDate->format('H:i:s'),
            'timeSpentSeconds' => $timeSpentSeconds,
            'description' => $description
        ]);
    }

    public function update($worklogId, $startedAt, $timeSpentSeconds, $description)
    {
        if (!$existingWorklog = $this->client->get('/worklogs/' . $worklogId)) {
            throw new UnexpectedValueException(sprintf('Worklog %d does not exists', $worklogId));
        }

        $startDate = new DateTime($startedAt);
        $putData = [
            'authorAccountId' => $existingWorklog['author']['accountId'],
            'issueKey' => $existingWorklog['issue']['key'],
            'startDate' => $startDate->format('Y-m-d'),
            'startTime' => $startDate->format('H:i:s'),
            'timeSpentSeconds' => $timeSpentSeconds,
            'description' => $description
        ];

        return $this->client->put('/worklogs/' . $worklogId, $putData);
    }

    public function delete($worklogId)
    {
        return $this->client->delete('/worklogs/' . $worklogId);
    }
}
