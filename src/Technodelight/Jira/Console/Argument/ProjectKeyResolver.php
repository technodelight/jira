<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Technodelight\GitShell\Api as Git;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Domain\Exception\MissingProjectKeyException;
use Technodelight\Jira\Domain\Project\ProjectKey;

class ProjectKeyResolver
{
    const ARGUMENT = 'projectKey';

    /**
     * @var Git
     */
    private $git;
    /**
     * @var ApplicationConfiguration
     */
    private $configuration;

    public function __construct(Git $git, ApplicationConfiguration $configuration)
    {
        $this->git = $git;
        $this->configuration = $configuration;
    }

    /**
     * @param InputInterface $input
     * @return ProjectKey|null
     */
    public function argument(InputInterface $input)
    {
        if (!$input->hasArgument(self::ARGUMENT)) {
            return null;
        }

        if ($projectKey = $this->fromString($input->getArgument(self::ARGUMENT))) {
            return $projectKey;
        }

        return null;
    }

    private function fromString($string)
    {
        try {
            return ProjectKey::fromString($string);
        } catch (MissingProjectKeyException $e) {
            return null;
        }
    }
}
