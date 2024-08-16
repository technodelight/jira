<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Action;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Helper;
use Technodelight\Jira\Domain\Issue\IssueKey;

/** @SuppressWarnings(PHPMD.StaticAccess) */
class StyleGuide
{
    public function __construct(private readonly FormatterHelper $formatterHelper) {}

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

    public function success($message): string
    {
        return $this->formatterHelper->formatBlock(
            Helper::removeDecoration(new OutputFormatter(), $message),
            'bg=green;fg=black',
            true
        ) . PHP_EOL;
    }

    public function error($message): string
    {
        return $this->formatterHelper->formatBlock(
            Helper::removeDecoration(new OutputFormatter(), $message),
            'error',
            true
        ) . PHP_EOL;
    }
}
