<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Technodelight\GitShell\ApiInterface as Git;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Domain\Exception\MissingProjectKeyException;
use Technodelight\Jira\Domain\Project\ProjectKey;

class ProjectKeyResolver
{
    const ARGUMENT = 'projectKey';

    public function __construct(
        private readonly Git $git,
        private readonly ApplicationConfiguration $configuration
    ) {}

    public function argument(InputInterface $input): ?ProjectKey
    {
        if (!$input->hasArgument(self::ARGUMENT)) {
            return null;
        }

        $projectKey = $this->fromString($input->getArgument(self::ARGUMENT));
        if (!empty($projectKey)) {
            return $projectKey;
        }

        return null;
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function fromString($string): ?ProjectKey
    {
        try {
            return ProjectKey::fromString($string);
        } catch (MissingProjectKeyException $e) {
            return null;
        }
    }
}
