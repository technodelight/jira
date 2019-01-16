<?php

namespace Technodelight\Jira\Renderer;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;

interface IssueRenderer
{
    public function render(OutputInterface $output, Issue $issue);
}
