<?php

namespace Technodelight\Jira\Console\Argument;

use Technodelight\GitShell\Branch;
use Technodelight\Jira\Domain\Exception\MissingProjectKeyException;

/**
 * @deprecated
 * @see \Technodelight\Jira\Domain\ProjectKey
 */
class ProjectKey
{
    const GIT_PATTERN = '~^feature/([A-Z]+-[0-9]+)-(.*)~';
    private $projectKey;

    public static function fromString($string)
    {
        if (empty(trim($string))) {
            throw new MissingProjectKeyException();
        }
        $instance = new self;
        $instance->projectKey = strtoupper(trim($string));
        return $instance;
    }

    /**
     * @deprecated
     * @param Branch $branch
     * @return ProjectKey
     */
    public static function fromBranch(Branch $branch)
    {
        $projectKey = '';
        if (preg_match(self::GIT_PATTERN, $branch->name(), $matches)) {
            list($projectKey, ) = explode('-', $matches[1], 2);
        }
        return self::fromString($projectKey);
    }

    public function __toString()
    {
        return $this->projectKey;
    }
}
