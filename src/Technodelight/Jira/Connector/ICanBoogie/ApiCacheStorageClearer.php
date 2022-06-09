<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\ICanBoogie;

use ICanBoogie\Storage\Storage;

class ApiCacheStorageClearer
{
    private Storage $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function clear(): void
    {
        $this->storage->clear();
    }
}
