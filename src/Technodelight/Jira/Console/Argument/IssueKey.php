<?php

namespace Technodelight\Jira\Console\Argument;

use Technodelight\GitShell\Branch;
use Technodelight\Jira\Console\Argument\Exception\MissingIssueKeyException;

/**
 * @todo: move this class under the domain namespace
 */
class IssueKey
{
    const ISSUE_PATTERN = '~^[A-Z]+-[0-9]+$~';
    const GIT_PATTERN = '~^feature/([A-Z]+-[0-9]+)-(.*)~';

    private $issueKey;
    private $issueId;
    private $projectKey;

    private function __construct($issueKey)
    {
         if (!preg_match(self::ISSUE_PATTERN, $issueKey)) {
            throw new MissingIssueKeyException;
        }
        $this->issueKey = $issueKey;
        list ($projectKey, $issueId) = explode('-', $issueKey, 2);
        $this->projectKey = $projectKey;
        $this->issueId = $issueId;
    }

    public static function fromString($issueKey)
    {
        return new IssueKey($issueKey);
    }

    /**
     * @deprecated since 0.9.15, will be removed in 0.9.17
     * @todo remove this method as unused
     * @param Branch $branch
     * @return IssueKey
     */
    public static function fromBranch(Branch $branch)
    {
        $issueKey = '';
        if (preg_match(self::GIT_PATTERN, $branch->name(), $matches)) {
            $issueKey = $matches[1];
        }
        return new IssueKey($issueKey);
    }

    public function projectKey()
    {
        return $this->projectKey;
    }

    public function issueId()
    {
        return $this->issueId;
    }

    public function __toString()
    {
        return (string) $this->issueKey;
    }
}
