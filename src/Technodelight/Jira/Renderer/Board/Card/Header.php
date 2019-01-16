<?php

namespace Technodelight\Jira\Renderer\Board\Card;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\SymfonyRgbOutputFormatter\PaletteOutputFormatterStyle;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Status;

class Header extends Base
{
    public function render(OutputInterface $output, Issue $issue)
    {
        $output->writeln($this->formatIssueKey($issue->status(), $issue->key()));
        $output->writeln(str_repeat('─', self::BLOCK_WIDTH));
    }

    private function formatIssueKey(Status $status, $issueKey)
    {
        $style = new PaletteOutputFormatterStyle;
        $style->setForeground('black');
        $style->setBackground($status->statusCategoryColor());
        $style->setOption('bold');
        return $style->apply(sprintf(' %s ', $issueKey));
    }
}
