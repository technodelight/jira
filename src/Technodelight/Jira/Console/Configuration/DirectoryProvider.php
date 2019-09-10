<?php

namespace Technodelight\Jira\Console\Configuration;

use Exception;
use Technodelight\GitShell\ApiInterface as Git;

class DirectoryProvider
{
    private $git;

    public function __construct(Git $git)
    {
        $this->git = $git;
    }

    public function project()
    {
        try {
            return $this->git->topLevelDirectory();
        } catch (Exception $exc) {
            return getcwd();
        }
    }

    public function user() {
        return getenv('HOME');
    }
}
