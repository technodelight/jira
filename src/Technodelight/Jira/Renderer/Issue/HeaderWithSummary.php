<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Renderer\IssueRenderer;

class HeaderWithSummary implements IssueRenderer
{
    public function render(OutputInterface $output, Issue $issue)
    {
        $output->writeln(
            sprintf(
                '<info>%s</info> %s',
                $issue->key(),
                $issue->summary()
            )
        );
    }
}
