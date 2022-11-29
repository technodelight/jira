<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\ICanBoogie;

use ICanBoogie\Storage\FileStorage;
use ICanBoogie\Storage\FileStorage\Adapter\JSONAdapter;
use ICanBoogie\Storage\RunTimeStorage;
use ICanBoogie\Storage\Storage;
use ICanBoogie\Storage\StorageCollection;

class ApiCacheStorageBuilder
{
    public function build(): Storage
    {
        return new StorageCollection([
            new RunTimeStorage(),
            new FileStorage(getenv('HOME') . DIRECTORY_SEPARATOR . '.jira/cache', new JSONAdapter())
        ]);
    }
}
