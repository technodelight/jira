<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Renderer\IssueRenderer;

class Renderer implements IssueRenderer
{
    /** @param IssueRenderer[] $renderers */
    public function __construct(private readonly array $renderers = []) {}

    public function render(OutputInterface $output, Issue $issue): void
    {
        foreach ($this->renderers as $renderer) {
            $renderer->render($output, $issue);
        }
    }
}
