<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\ProjectKeyResolver;
use Technodelight\Jira\Renderer\Project\Renderer;

class Project extends Command
{
    /**
     * @var ProjectKeyResolver
     */
    private $projectKeyResolver;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Renderer
     */
    private $projectRenderer;
    /**
     * @var Renderer
     */
    private $fullProjectRenderer;

    public function __construct(
        ProjectKeyResolver $projectKeyResolver,
        Api $api,
        Renderer $projectRenderer,
        Renderer $fullProjectRenderer
    )
    {
        $this->projectKeyResolver = $projectKeyResolver;
        $this->api = $api;
        $this->projectRenderer = $projectRenderer;
        $this->fullProjectRenderer = $fullProjectRenderer;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('show:project')
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
        $projectKey = $this->projectKeyResolver->argument($input);
        if (null === $projectKey) {
            throw new \InvalidArgumentException('Please specify project key!');
        }
        $project = $this->api->project($projectKey);

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $this->fullProjectRenderer->render($output, $project);
        } else {
            $this->projectRenderer->render($output, $project);
        }
    }
}
