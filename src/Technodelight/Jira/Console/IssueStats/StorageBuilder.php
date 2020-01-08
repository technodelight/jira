<?php

namespace Technodelight\Jira\Console\IssueStats;

use ICanBoogie\Storage\Codec\JSONCodec;
use ICanBoogie\Storage\FileStorage;
use ICanBoogie\Storage\RunTimeStorage;
use ICanBoogie\Storage\StorageCollection;

class StorageBuilder
{
    public static function build()
    {
        return new StorageCollection([
            new RunTimeStorage(),
            new FileStorage(getenv('HOME') . DIRECTORY_SEPARATOR . '.jira/stats', new JSONCodec)
        ]);
    }
}
