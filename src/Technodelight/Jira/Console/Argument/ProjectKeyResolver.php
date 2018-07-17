<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Technodelight\GitShell\Api as Git;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Console\Argument\Exception\MissingProjectKeyException;

class ProjectKeyResolver
{
    const ARGUMENT = 'projectKey';
    /**
     * @var \Technodelight\GitShell\Api
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

        if ($projectKey = $this->fromString($input->getArgument(self::ARGUMENT))) {
            return $projectKey;
        }
        if ($projectKey = $this->fromBranch($this->git->currentBranch())) {
            return $projectKey;
        }
    }

    private function fromString($string)
    {
        try {
            return ProjectKey::fromString($string);
        } catch (MissingProjectKeyException $e) {
            return false;
        }
    }

    private function fromBranch($branch)
    {
        try {
            return ProjectKey::fromBranch($branch);
        } catch (MissingProjectKeyException $e) {
            return false;
        }
    }
}
