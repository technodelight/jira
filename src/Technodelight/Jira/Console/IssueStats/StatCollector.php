<?php

namespace Technodelight\Jira\Console\IssueStats;

use ICanBoogie\Storage\Storage;

class StatCollector
{
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

    public function all()
    {
        $stats = new Stats;
        foreach ($this->cacheKeys() as $issueKey) {
            $events = $this->serializer->unserialize($this->storage->retrieve($issueKey));
            $stats->collectEvents($issueKey, $events);
        }

        return $stats;
    }

    private function cacheKeys()
    {
        $keys = [];
        foreach (glob($this->statDir() . DIRECTORY_SEPARATOR . '*.ttl') as $file) {
            $keys[] = pathinfo($file, PATHINFO_FILENAME);
        }
        return $keys;
    }

    private function statDir()
    {
        return getenv('HOME') . DIRECTORY_SEPARATOR . '.jira.stats';
    }
}
