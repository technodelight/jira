<?php

namespace Technodelight\Jira\Console\Argument\IssueKeyResolver;

use Technodelight\GitShell\Branch;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration\BranchNameGeneratorConfiguration;
use Technodelight\Jira\Domain\Exception\MissingIssueKeyException;
use Technodelight\Jira\Domain\Issue\IssueKey;

class Guesser
{
    /**
     * @var AliasesConfiguration
     */
    private $aliasConfig;
    /**
     * @var BranchNameGeneratorConfiguration
     */
    private $branchConfig;

    public function __construct(AliasesConfiguration $aliasConfig, BranchNameGeneratorConfiguration $branchConfig)
    {
        $this->aliasConfig = $aliasConfig;
        $this->branchConfig = $branchConfig;
    }

    public function guessIssueKey($guessable, Branch $currentBranch = null)
    {
        if ($key = $this->fromString($guessable)) {
            return $key;
        }
        if ($key = $this->fromUrl($guessable)) {
            return $key;
        }
        if (!is_null($currentBranch) && $key = $this->fromBranch($currentBranch)) {
            return $key;
        }

        return null;
    }

    private function fromString($string)
    {
        try {
            return IssueKey::fromString($this->aliasConfig->aliasToIssueKey($string));
        } catch (MissingIssueKeyException $exception) {
            return null;
        }
    }

    private function fromBranch(Branch $branch)
    {
        try {
            $issueKey = $this->aliasConfig->aliasToIssueKey($branch->name());
            if ($issueKey != $branch->name()) { // has an alias for complete branch name
                return IssueKey::fromString($issueKey);
            }
            return $this->findIssueKeyFromBranch($branch);
        } catch (MissingIssueKeyException $exception) {
            return null;
        }
    }

    private function findIssueKeyFromBranch(Branch $branch)
    {
        $regexes = [];
        foreach ($this->branchConfig->patterns() as $pattern) {
            if (preg_match_all('~({[^}]+})~', $pattern, $matches)) {
                $regex = $pattern;
                if (!isset($matches[1])) {
                    continue;
                }
                foreach ($matches[1] as $match) {
                    $regex = str_replace($match, '(.*)', $regex);
                }
                $regexes[$pattern] = $regex;
            }
        }

        foreach ($regexes as $regex) {
            if (preg_match('~' . $regex . '~', $branch->name(), $matches)) {
                foreach ($matches as $match) {
                    try {
                        if (!preg_match('~[A-Z]+-[0-9]+~', $match, $subMatches)) {
                            continue;
                        }
                        return IssueKey::fromString($subMatches[0]);
                    } catch (MissingIssueKeyException $e) {

                    }
                }
            }
        }

        return null;
    }

    private function fromUrl($guessable)
    {
        if (preg_match('~https?://.*/([A-Z]+-[0-9]+).*~', $guessable, $matches)) {
            try {
                return IssueKey::fromString($matches[1]);
            } catch (MissingIssueKeyException $e) {
                return null;
            }
        }
    }
}
