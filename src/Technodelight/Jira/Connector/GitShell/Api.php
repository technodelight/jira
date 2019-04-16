<?php

namespace Technodelight\Jira\Connector\GitShell;

use Technodelight\GitShell\ApiInterface as Git;
use Technodelight\GitShell\ApiInterface;
use Technodelight\GitShell\Branch;
use Technodelight\GitShell\Remote;

class Api implements ApiInterface
{
    /**
     * @var Git
     */
    private $git;

    public function __construct(Git $git)
    {
        $this->git = $git;
    }

    /**
     * @param string $from
     * @param string $to
     * @return \Generator
     */
    public function log($from, $to = 'head')
    {
        try {
            return $this->git()->log($from, $to);
        } catch (\InvalidArgumentException $e) {
            yield from [];
        }
    }

    /**
     * @param string $branch
     * @return void
     */
    public function createBranch($branch)
    {
        $this->git()->createBranch($branch);
    }

    /**
     * @param string $branch
     * @return void
     */
    public function switchBranch($branch)
    {
        $this->git()->switchBranch($branch);
    }

    /**
     * @param bool $verbose
     * @return Remote[]
     */
    public function remotes($verbose = false)
    {
        try {
            return $this->git()->remotes($verbose);
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * @param string $pattern optional
     * @param bool $withRemotes include remotes or not
     * @return Branch[]
     */
    public function branches($pattern = '', $withRemotes = true)
    {
        try {
            return $this->git()->branches($pattern, $withRemotes);
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * @return Branch|null
     */
    public function currentBranch()
    {
        try {
            return $this->git()->currentBranch();
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @TODO this often lies, the goal would be to find a branch's first parent
     * @return false|string
     */
    public function parentBranch()
    {
        try {
            return $this->git()->parentBranch();
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @return string|null
     */
    public function topLevelDirectory()
    {
        try {
            return $this->git()->topLevelDirectory();
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Get name and status diff for current branch
     *
     * @param string|null $to
     * @return \Technodelight\GitShell\DiffEntry[]
     */
    public function diff($to = null)
    {
        try {
            return $this->git()->diff($to);
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * @return Git
     */
    private function git()
    {
        if ($this->git->topLevelDirectory()) {
            return $this->git;
        }

        throw new \InvalidArgumentException('Not a git repo');
    }
}
