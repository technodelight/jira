<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Technodelight\GitShell\ApiInterface as Git;

class FilenameProvider
{
    const FILENAME = '.jira.yml';
    const MODULE_PATH = '.jira/container/*/modules/*';

    private $git;

    public function __construct(Git $git)
    {
        $this->git = $git;
    }

    public function projectFile()
    {
        try {
            return $this->git->topLevelDirectory() . DIRECTORY_SEPARATOR . self::FILENAME;
        } catch (\Exception $exc) {
            return getcwd() . DIRECTORY_SEPARATOR . self::FILENAME;
        }
    }

    public function userFile() {
        return getenv('HOME') . DIRECTORY_SEPARATOR . self::FILENAME;
    }

    /**
     * @return string[]
     */
    public function moduleFiles()
    {
        $files = [];
        foreach (glob(self::MODULE_PATH, GLOB_ONLYDIR) as $moduleDirectory) {
            if (is_file($moduleDirectory . DIRECTORY_SEPARATOR . self::FILENAME)) {
                $files[] = $moduleDirectory . DIRECTORY_SEPARATOR . self::FILENAME;
            }
        }

        return $files;
    }
}
