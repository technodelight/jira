<?php


namespace Technodelight\Jira\Renderer\Issue;


use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Renderer\IssueRenderer;

class MinimalHeader implements IssueRenderer
{
    public function render(OutputInterface $output, Issue $issue)
    {
        $output->writeln($issue->key());
    }
}
