<?php

namespace Technodelight\Jira\Renderer\Board\Card;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Renderer\Board\Renderer;

class Summary extends Base
{
    public function render(OutputInterface $output, Issue $issue): void
    {
        $output->writeln($this->wordwrap->shorten($issue->summary(), Renderer::BLOCK_WIDTH));
    }
}
