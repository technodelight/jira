<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\IssueStats;

use ICanBoogie\Storage\Storage;

class IssueStats
{
    private const CACHE_TTL = 604800;

    public function __construct(
        private readonly Storage $storage,
        private readonly Serializer $serializer
    ) {}

    public function view($issueKey): void
    {
        $this->captureEvent($issueKey, Event::VIEW);
    }

    public function update($issueKey): void
    {
        $this->captureEvent($issueKey, Event::UPDATE);
    }

    public function clear(): void
    {
        $this->storage->clear();
    }

    /**
     * @param string $issueKey
     * @param string $eventType
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function captureEvent(string $issueKey, string $eventType): void
    {
        if (empty($issueKey)) {
            return;
        }

        $data = $this->retrieve($issueKey);
        $data[] = Event::fromString($eventType);
        $this->store($issueKey, $data);
    }

    private function retrieve($issueKey): array
    {
        $data = [];
        if ($this->storage->exists($issueKey)) {
            $data = $this->storage->retrieve($issueKey);
        }

        return $this->serializer->unserialize($data);
    }

    private function store($issueKey, array $data): void
    {
        $this->storage->store(
            $issueKey,
            $this->serializer->serialize($data),
            self::CACHE_TTL
        );
    }
}
