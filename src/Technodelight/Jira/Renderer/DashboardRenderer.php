<?php

namespace Technodelight\Jira\Renderer;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Dashboard\Collection;

interface DashboardRenderer
{
    public function render(OutputInterface $output, Collection $collection);
}