<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Renderer\IssueRenderer;

class Renderer implements IssueRenderer
{
    /**
     * @var IssueRenderer[]
     */
    private $renderers;

    public function __construct(array $renderers)
    {
        $this->renderers = $renderers;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        foreach ($this->renderers as $renderer) {
            $renderer->render($output, $issue);
        }
    }
}
