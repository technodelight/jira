<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\GitShell\Api as Git;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Console\Argument\Exception\MissingIssueKeyException;

class IssueKeyResolver
{
    private $git;
    private $configuration;

    const ARGUMENT = 'issueKey';
    const OPTION = 'issueKey';
    /**
     * @var \Technodelight\Jira\Console\Argument\InteractiveIssueSelector
     */
    private $issueSelector;

    public function __construct(Git $git, ApplicationConfiguration $configuration, InteractiveIssueSelector $issueSelector)
    {
        $this->git = $git;
        $this->configuration = $configuration;
        $this->issueSelector = $issueSelector;
    }

    public function argument(InputInterface $input, OutputInterface $output)
    {
        if (!$input->hasArgument(self::ARGUMENT)) {
            return null;
        }
        $value = $this->resolve($input->getArgument(self::ARGUMENT), $input, $output);
        $input->setArgument(self::ARGUMENT, (string) $value);
        return $value;
    }

    public function option(InputInterface $input, OutputInterface $output)
    {
        if (!$input->hasOption(self::OPTION)) {
            return null;
        }
        $value = $this->resolve($input->getOption(self::OPTION), $input, $output);
        $input->setOption(self::OPTION, (string) $value);
        return $value;
    }

    private function resolve($argumentOrOption, InputInterface $input, OutputInterface $output)
    {
        if ($key = $this->fromString($argumentOrOption)) {
            return $key;
        }
        if ($key = $this->fromBranch($this->git->currentBranch())) {
            return $key;
        }

        return $this->fromString($this->issueSelector->chooseIssue($input, $output)->key());
    }

    private function alias($argumentOrOption)
    {
        $aliases = $this->configuration->aliases();
        return isset($aliases[$argumentOrOption]) ? $aliases[$argumentOrOption] : $argumentOrOption;
    }

    private function fromString($string)
    {
        try {
            return IssueKey::fromString($this->alias($string));
        } catch (MissingIssueKeyException $exception) {
            return false;
        }
    }

    private function fromBranch($branch)
    {
        try {
            return IssueKey::fromBranch($branch);
        } catch (MissingIssueKeyException $exception) {
            return false;
        }
    }
}
