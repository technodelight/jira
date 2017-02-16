<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Technodelight\Jira\Api\GitShell\Api as Git;
use Technodelight\Jira\Helper\GitHelper;

class FilenameProvider
{
    const FILENAME = '.jira.yml';

    private $git;

    public function __construct(Git $git)
    {
        $this->git = $git;
    }

    public function localFile()
    {
        return $this->git->topLevelDirectory() . DIRECTORY_SEPARATOR . self::FILENAME;
    }

    public function globalFile() {
        return getenv('HOME') . DIRECTORY_SEPARATOR . self::FILENAME;
    }
}
