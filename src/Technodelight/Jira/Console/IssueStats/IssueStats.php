<?php

namespace Technodelight\Jira\Console\IssueStats;

use ICanBoogie\Storage\Storage;

class IssueStats
{
    const CACHE_TTL = 604800;

    /**
     * @var \ICanBoogie\Storage\Storage
     */
    private $storage;
    /**
     * @var \Technodelight\Jira\Console\IssueStats\Serializer
     */
    private $serializer;

    public function __construct(Storage $storage, Serializer $serializer)
    {
        $this->storage = $storage;
        $this->serializer = $serializer;
    }

    public function view($issueKey)
    {
        $this->captureEvent($issueKey, Event::VIEW);
    }

    public function update($issueKey)
    {
        $this->captureEvent($issueKey, Event::UPDATE);
    }

    public function clear()
    {
        $this->storage->clear();
    }

    /**
     * @param string $issueKey
     * @param string $eventType
     */
    private function captureEvent($issueKey, $eventType)
    {
        if (empty($issueKey)) {
            return;
        }

        $data = $this->retrieve($issueKey);
        $data[] = Event::fromString($eventType);
        $this->store($issueKey, $data);
    }

    private function retrieve($issueKey)
    {
        if ($this->storage->exists($issueKey)) {
            $data = $this->storage->retrieve($issueKey);
        } else {
            $data = [];
        }

        return $this->serializer->unserialize($data);
    }

    private function store($issueKey, array $data)
    {
        $this->storage->store(
            $issueKey,
            $this->serializer->serialize($data),
            self::CACHE_TTL
        );
    }
}
