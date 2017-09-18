<?php

namespace Technodelight\Jira\Api\Tempo;

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

    public function find($dateFrom, $dateTo)
    {
        return $this->client->get('/worklogs', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo]);
    }

    public function create($issueKey, $startedAt, $timeSpentSeconds, $comment)
    {
        return $this->client->post('/worklogs', [
            'issue' => ['key' => $issueKey],
            'dateStarted' => $startedAt,
            'timeSpentSeconds' => $timeSpentSeconds,
            'comment' => $comment
        ]);
    }

    public function update($worklogId, $startedAt, $timeSpentSeconds, $comment)
    {
        return $this->client->put('/' . $worklogId, [
            'dateStarted' => $startedAt,
            'timeSpentSeconds' => $timeSpentSeconds,
            'comment' => $comment
        ]);
    }

    public function delete($worklogId)
    {
        return $this->client->delete('/' . $worklogId);
    }
}
