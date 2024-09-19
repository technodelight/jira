<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Configuration;

use Exception;
use Technodelight\GitShell\ApiInterface as Git;

readonly class DirectoryProvider
{
    public function __construct(private Git $git)
    {
    }

    public function project(): string
    {
        try {
            $topLevelDirectory = $this->git->topLevelDirectory();
            if ($topLevelDirectory !== null) {
                return $topLevelDirectory;
            }
        } catch (Exception $exc) {
            // ignore exception
        }

        return getcwd() ?: '.';
    }

    public function user(): string
    {
        return getenv('HOME');
    }

    public function dotConfig(): string
    {
        return getenv('HOME') . '/.config/jira';
    }
}
