<?php

namespace Technodelight\Jira\Connector\GitShell;

use Exception;
use Generator;
use InvalidArgumentException;
use Technodelight\GitShell\ApiInterface as Git;
use Technodelight\GitShell\ApiInterface;
use Technodelight\GitShell\Branch;

/** @SuppressWarnings(PHPMD) */
class Api implements ApiInterface
{

    public function __construct(private readonly Git $git) {}

    public function log($from, $to = 'head'): Generator
    {
        try {
            return $this->git()->log($from, $to);
        } catch (Exception $e) {
            yield from [];
        }
    }
    public function createBranch($branch): void
    {
        $this->git()->createBranch($branch);
    }

    public function switchBranch($branch): void
    {
        $this->git()->switchBranch($branch);
    }

    public function remotes($verbose = false): array
    {
        try {
            return $this->git()->remotes($verbose);
        } catch (Exception $e) {
            return [];
        }
    }

    public function branches($pattern = '', $withRemotes = true): array
    {
        try {
            return $this->git()->branches($pattern, $withRemotes);
        } catch (Exception $e) {
            return [];
        }
    }

    public function currentBranch(): ?Branch
    {
        try {
            return $this->git()->currentBranch();
        } catch (Exception $e) {
            return null;
        }
    }

    /** @TODO this often lies, the goal would be to find a branch's first parent */
    public function parentBranch(): string|bool
    {
        try {
            return $this->git()->parentBranch();
        } catch (Exception $e) {
            return false;
        }
    }

    public function topLevelDirectory(): ?string
    {
        try {
            return $this->git()->topLevelDirectory();
        } catch (Exception $e) {
            return null;
        }
    }

    public function diff($to = null): array
    {
        try {
            return $this->git()->diff($to);
        } catch (Exception $e) {
            return [];
        }
    }

    private function git(): Git
    {
        if ($this->git->topLevelDirectory()) {
            return $this->git;
        }

        throw new InvalidArgumentException('Not a git repo');
    }
}
