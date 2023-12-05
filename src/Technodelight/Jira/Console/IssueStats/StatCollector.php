<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\IssueStats;

use ICanBoogie\Storage\Storage;

class StatCollector
{
    public function __construct(
        private readonly Storage $storage,
        private readonly Serializer $serializer
    ) {
    }

    public function all(): Stats
    {
        $stats = new Stats;
        foreach ($this->cacheKeys() as $issueKey) {
            $events = $this->serializer->unserialize($this->storage->retrieve($issueKey));
            $stats->collectEvents($issueKey, $events);
        }

        return $stats;
    }

    private function cacheKeys(): array
    {
        $keys = [];
        foreach (glob($this->statDir() . DIRECTORY_SEPARATOR . '*.ttl') as $file) {
            $keys[] = pathinfo($file, PATHINFO_FILENAME);
        }
        return $keys;
    }

    private function statDir(): string
    {
        return getenv('HOME') . DIRECTORY_SEPARATOR . '.jira/stats';
    }
}
