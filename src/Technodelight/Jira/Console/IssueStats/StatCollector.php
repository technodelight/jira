<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\IssueStats;

use ICanBoogie\Storage\Storage;
use Technodelight\Jira\Console\Configuration\DirectoryProvider;

class StatCollector
{
    public function __construct(
        private readonly DirectoryProvider $directoryProvider,
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
        return array_map(
            fn(string $filename) => pathinfo($filename, PATHINFO_FILENAME),
            glob($this->statDir() . DIRECTORY_SEPARATOR . '*.ttl')
        );
    }

    private function statDir(): string
    {
        return $this->directoryProvider->user() . DIRECTORY_SEPARATOR . '.jira/stats';
    }
}
