<?php

namespace Technodelight\Jira\Api\JiraRestApi;

use ICanBoogie\Storage\Codec\JSONCodec;
use ICanBoogie\Storage\FileStorage;
use ICanBoogie\Storage\RunTimeStorage;
use ICanBoogie\Storage\StorageCollection;

class ApiCacheStorageBuilder
{
    public function build()
    {
        return new StorageCollection([
            new RunTimeStorage(),
            new FileStorage(getenv('HOME') . DIRECTORY_SEPARATOR . '.jira/cache', new JSONCodec)
        ]);
    }
}
