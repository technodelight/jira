<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Technodelight\Jira\Api\GitShell\Api as Git;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Console\Argument\Exception\MissingIssueKeyException;

class IssueKeyResolver implements Resolver
{
    private $git;
    private $configuration;

    const ARGUMENT = 'issueKey';
    const OPTION = 'issueKey';

    public function __construct(Git $git, Configuration $configuration)
    {
        $this->git = $git;
        $this->configuration = $configuration;
    }

    public function argument(InputInterface $input)
    {
        if (!$input->hasArgument(self::ARGUMENT)) {
            return null;
        }
        return $this->resolve($input->getArgument(self::ARGUMENT));
    }

    public function option(InputInterface $input)
    {
        if (!$input->hasOption(self::OPTION)) {
            return null;
        }
        return $this->resolve($input->getOption(self::OPTION));
    }

    private function resolve($argumentOrOption)
    {
        try {
            $issueKey = IssueKey::fromString($this->alias($argumentOrOption));
        } catch (MissingIssueKeyException $e) {
            $issueKey = IssueKey::fromBranch($this->git->currentBranch());
        }
        return $issueKey;
    }

    private function alias($argumentOrOption)
    {
        $aliases = $this->configuration->aliases();
        return isset($aliases[$argumentOrOption]) ? $aliases[$argumentOrOption] : $argumentOrOption;
    }
}
