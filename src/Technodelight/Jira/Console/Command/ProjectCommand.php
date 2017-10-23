<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('project')
            ->setDescription('Show project details by project key')
            ->addArgument(
                'projectKey',
                InputArgument::OPTIONAL,
                'Project to show. Can guess project from current feature branch'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $project = $this->jiraApi()->project($this->projectKeyResolver()->argument($input));

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $this->fullProjectRenderer()->render($output, $project);
        } else {
            $this->projectRenderer()->render($output, $project);
        }
    }

    /**
     * @return \Technodelight\Jira\Console\Argument\ProjectKeyResolver
     */
    private function projectKeyResolver()
    {
        return $this->getService('technodelight.jira.console.argument.project_key_resolver');
    }

    /**
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }

    /**
     * @return \Technodelight\Jira\Renderer\Project\Renderer
     */
    private function projectRenderer()
    {
        return $this->getService('technodelight.jira.renderer.project');
    }

    /**
     * @return \Technodelight\Jira\Renderer\Project\Renderer
     */
    private function fullProjectRenderer()
    {
        return $this->getService('technodelight.jira.renderer.project.full');
    }
}
