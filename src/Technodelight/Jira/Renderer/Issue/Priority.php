<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;

class Priority implements IssueRenderer
{
    public function __construct(private readonly TemplateHelper $templateHelper)
    {
    }

    public function render(OutputInterface $output, Issue $issue): void
    {
        if ($issue->priority()->name() !== '') {
            $output->writeln(
                $this->tab(sprintf('<comment>priority:</comment> %s', $issue->priority()))
            );
        }
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
