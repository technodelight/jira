<?php

namespace Technodelight\Jira\Renderer\Project;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Project;
use Technodelight\Jira\Domain\User;
use Technodelight\Jira\Renderer\ProjectRenderer;

class Header implements ProjectRenderer
{
    public function render(OutputInterface $output, Project $project)
    {
        $output->writeln(
            sprintf(
                '<bg=blue;fg=white;options=bold> %s </> %s %s',
                $project->key(),
                $project->name(),
                $project->lead() ? $this->renderLead($project->lead()) : ''
            )
        );
    }

    private function renderLead(User $lead)
    {
        return sprintf(
            '(lead: %s)',
            $lead->displayName()
        );
    }
}
