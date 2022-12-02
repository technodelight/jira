<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;

class Priority implements IssueRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;

    public function __construct(TemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public function render(OutputInterface $output, Issue $issue): void
    {
        if ($priority = $issue->priority()) {
            $output->writeln(
                $this->tab(sprintf('<comment>priority:</comment> %s', $priority))
            );
        }
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
