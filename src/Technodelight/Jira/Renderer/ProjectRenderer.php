<?php

namespace Technodelight\Jira\Renderer;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Project;

interface ProjectRenderer
{
    public function render(OutputInterface $output, Project $project);
}
