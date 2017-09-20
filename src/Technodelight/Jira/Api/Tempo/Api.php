<?php

namespace Technodelight\Jira\Api\Tempo;

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
        return $this->client->get('/worklogs', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo]);
    }

    public function retrieve($worklogId)
    {
        return $this->client->get('/worklogs/' . $worklogId);
    }

    public function create($issueKey, $authorName, $startedAt, $timeSpentSeconds, $comment)
    {
        return $this->client->post('/worklogs', [
            'author' => ['name' => $authorName],
            'issue' => ['key' => $issueKey],
            'dateStarted' => $startedAt,
            'timeSpentSeconds' => $timeSpentSeconds,
            'comment' => $comment
        ]);
    }

    public function update($worklogId, $startedAt, $timeSpentSeconds, $comment)
    {
        return $this->client->put('/worklogs/' . $worklogId, [
            'dateStarted' => $startedAt,
            'timeSpentSeconds' => $timeSpentSeconds,
            'comment' => $comment
        ]);
    }

    public function delete($worklogId)
    {
        return $this->client->delete('/worklogs/' . $worklogId);
    }
}
