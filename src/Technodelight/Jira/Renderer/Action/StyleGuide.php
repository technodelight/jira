<?php

namespace Technodelight\Jira\Renderer\Action;

use Technodelight\Jira\Domain\Issue\IssueKey;

class StyleGuide
{
    public function formatIssueKey(IssueKey $issueKey): string
    {
        return sprintf('<info>%s</>', $issueKey);
    }

    public function formatUsername($username): string
    {
        return sprintf('<fg=cyan>%s</>', $username);
    }

    public function formatTransition($transition): string
    {
        return sprintf('<comment>%s</>', $transition);
    }

    public function formatFirstLevelInfo($firstLevelInfo): string
    {
        return sprintf('<comment>%s</>', $firstLevelInfo);
    }
}
