<?php

namespace Technodelight\Jira\Api\Tempo2;

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
        return $this->client->get('/worklogs', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo])['results'];
    }

    public function all()
    {
        return $this->client->get('/worklogs')['results'];
    }

    public function retrieve($worklogId)
    {
        return $this->client->get('/worklogs/' . $worklogId);
    }

    public function create($issueKey, $authorUsername, $startedAt, $timeSpentSeconds, $description)
    {
        $startDate = new \DateTime($startedAt);
        return $this->client->post('/worklogs', [
            'authorUsername' => $authorUsername,
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
            throw new \UnexpectedValueException(sprintf('Worklog %d does not exists', $worklogId));
        }

        $startDate = new \DateTime($startedAt);
        $putData = [
            'authorUsername' => $existingWorklog['author']['username'],
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
