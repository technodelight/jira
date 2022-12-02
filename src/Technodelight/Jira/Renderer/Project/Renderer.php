<?php

namespace Technodelight\Jira\Renderer\Project;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Project;
use Technodelight\Jira\Renderer\ProjectRenderer;

class Renderer implements ProjectRenderer
{
    /**
     * @var array
     */
    private $renderers;

    /**
     * Renderer constructor.
     *
     * @param ProjectRenderer[] $renderers
     */
    public function __construct(array $renderers)
    {
        $this->renderers = $renderers;
    }

    public function render(OutputInterface $output, Project $project): void
    {
        foreach ($this->renderers as $renderer) {
            $renderer->render($output, $project);
        }
    }
}
