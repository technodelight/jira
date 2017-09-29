<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Renderer;

class UserDetails implements Renderer
{
    const UNASSIGNED = 'Unassigned';
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;

    public function __construct(TemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        $output->writeln($this->templateHelper->tabulate($this->userDetails($issue)));
    }

    private function userDetails(Issue $issue)
    {
        return trim($this->assignee($issue) . ' ' . $this->reporter($issue));
    }

    private function assignee(Issue $issue)
    {
        if ($issue->assignee() != self::UNASSIGNED && !empty($issue->assignee())) {
            return sprintf('<comment>assignee:</comment> %s', $issue->assignee());
        }
    }

    private function reporter(Issue $issue)
    {
        if ($issue->reporter() != self::UNASSIGNED && !empty($issue->reporter())) {
            return sprintf('<comment>reporter:</comment> %s', $issue->reporter());
        }
    }
}
