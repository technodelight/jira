<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Argument\IssueKeyResolver;

use Technodelight\GitShell\Branch;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration\BranchNameGeneratorConfiguration;
use Technodelight\Jira\Domain\Exception\MissingIssueKeyException;
use Technodelight\Jira\Domain\Issue\IssueKey;

class Guesser
{
    public function __construct(
        private readonly AliasesConfiguration $aliasConfig,
        private readonly BranchNameGeneratorConfiguration $branchConfig
    ) {}

    public function guessIssueKey($guessable, ?Branch $currentBranch = null): ?IssueKey
    {
        $fromString = $this->fromString((string)$guessable);
        $fromUrl = $this->fromUrl($guessable);
        $fromBranch =  $this->fromBranch($currentBranch);

        return match(true) {
            !empty($fromString) => $fromString,
            !empty($fromUrl) => $fromUrl,
            $currentBranch !== null && !empty($fromBranch) => $fromBranch,
            default => null
        };
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function fromString($string): ?IssueKey
    {
        try {
            return IssueKey::fromString($this->aliasConfig->aliasToIssueKey($string));
        } catch (MissingIssueKeyException $exception) {
            return null;
        }
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function fromBranch(?Branch $branch): ?IssueKey
    {
        try {
            if ($branch === null) {
                return null;
            }
            $issueKey = $this->aliasConfig->aliasToIssueKey($branch->name());
            if ($issueKey !== $branch->name()) { // has an alias for complete branch name
                return IssueKey::fromString($issueKey);
            }
            return $this->findIssueKeyFromBranch($branch);
        } catch (MissingIssueKeyException $exception) {
            return null;
        }
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */

    private function findIssueKeyFromBranch(Branch $branch): ?IssueKey
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
                        if (!preg_match('~' . IssueKey::PATTERN . '~', $match, $subMatches)) {
                            continue;
                        }
                        return IssueKey::fromString($subMatches[0]);
                    } catch (MissingIssueKeyException $e) {
                        // no-op
                    }
                }
            }
        }

        return null;
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function fromUrl($guessable): ?IssueKey
    {
        if (preg_match('~https?://.*/(' . IssueKey::PATTERN . ').*~', (string)$guessable, $matches)) {
            try {
                return IssueKey::fromString($matches[1]);
            } catch (MissingIssueKeyException $e) {
                return null;
            }
        }

        return null;
    }
}
