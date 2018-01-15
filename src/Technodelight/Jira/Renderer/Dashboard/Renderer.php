<?php

namespace Technodelight\Jira\Renderer\Dashboard;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Dashboard\Collection;
use Technodelight\Jira\Renderer\DashboardRenderer;

class Renderer implements DashboardRenderer
{
    /**
     * @var DashboardRenderer[]
     */
    private $renderers;

    public function __construct(array $renderers)
    {
        $this->renderers = $renderers;
    }

    public function render(OutputInterface $output, Collection $collection)
    {
        foreach ($this->renderers as $renderer) {
            $renderer->render($output, $collection);
        }
    }
}