<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Technodelight\Jira\Api\GitShell\Api as Git;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Console\Argument\Exception\MissingProjectKeyException;

class ProjectKeyResolver
{
    const ARGUMENT = 'projectKey';
    /**
     * @var \Technodelight\Jira\Api\GitShell\Api
     */
    private $git;
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration
     */
    private $configuration;

    public function __construct(Git $git, ApplicationConfiguration $configuration)
    {
        $this->git = $git;
        $this->configuration = $configuration;
    }

    public function argument(InputInterface $input)
    {
        if (!$input->hasArgument(self::ARGUMENT)) {
            return null;
        }

        try {
            $projectKey = ProjectKey::fromString($input->getArgument(self::ARGUMENT));
        } catch (MissingProjectKeyException $e) {
            $projectKey = ProjectKey::fromBranch($this->git->currentBranch());
        }
        return $projectKey;
    }
}
