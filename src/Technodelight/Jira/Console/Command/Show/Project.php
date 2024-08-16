<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Command\Show;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\ProjectKeyResolver;
use Technodelight\Jira\Renderer\Project\Renderer;

class Project extends Command
{
    public function __construct(
        private readonly ProjectKeyResolver $projectKeyResolver,
        private readonly Api $api,
        private readonly Renderer $projectRenderer,
        private readonly Renderer $fullProjectRenderer
    ) {
        parent::__construct();
    }

    protected function configure(): void
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectKey = $this->projectKeyResolver->argument($input);
        if (null === $projectKey) {
            throw new InvalidArgumentException('Please specify project key!');
        }
        $project = $this->api->project($projectKey);

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $this->fullProjectRenderer->render($output, $project);
            return self::SUCCESS;
        }

        $this->projectRenderer->render($output, $project);
        return self::SUCCESS;
    }
}
