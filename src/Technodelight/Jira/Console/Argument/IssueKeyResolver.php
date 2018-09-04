<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\GitShell\Api as Git;
use Technodelight\GitShell\Branch;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Console\Argument\Exception\MissingIssueKeyException;

/**
 * @TODO: this class should be more powerful.
 * @TODO: it should re-use the pattern defs (may need rework) for recognising issues from branch name
 * @TODO: this should be also working with URLs like https://instance.atlassian.net/browse/ISSUE-123
 */
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

    public function __construct(Git $git, AliasesConfiguration $configuration, InteractiveIssueSelector $issueSelector)
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
        $issueKey = $this->resolve($input->getArgument(self::ARGUMENT), $input, $output);

        if (!empty($issueKey)) {
            $shift = false;
            foreach ($input->getArguments() as $argument => $value) {
                if ($argument == self::ARGUMENT && $this->isArgValueAnIssueKey($value, $issueKey)) {
                    $shift = true;
                    $previousArgumentValue = $input->getArgument(self::ARGUMENT);
                    $input->setArgument($argument, (string) $issueKey);
                } else if ($shift && isset($previousArgumentValue)) {
                    $input->setArgument($argument, $previousArgumentValue);
                    $previousArgumentValue = $value;
                }
            }
        }
        return $issueKey;
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

    private function fromString($string)
    {
        try {
            return IssueKey::fromString($this->configuration->aliasToIssueKey($string));
        } catch (MissingIssueKeyException $exception) {
            return false;
        }
    }

    private function fromBranch(Branch $branch)
    {
        try {
            $issueKey = $this->configuration->aliasToIssueKey($branch->name());
            if ($issueKey != $branch->name()) { // has an alias for branch
                return IssueKey::fromString($issueKey);
            }
            return IssueKey::fromBranch($branch);
        } catch (MissingIssueKeyException $exception) {
            return false;
        }
    }

    private function isArgValueAnIssueKey($value, $issueKey)
    {
        return ($value != $issueKey)
            && ($value != $this->configuration->issueKeyToAlias($issueKey));
    }
}
