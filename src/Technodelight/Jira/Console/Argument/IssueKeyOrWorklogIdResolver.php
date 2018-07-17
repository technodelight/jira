<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Technodelight\GitShell\Api;
use Technodelight\GitShell\Branch;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Console\Argument\Exception\MissingIssueKeyException;

class IssueKeyOrWorklogIdResolver
{
    const NAME = 'issueKeyOrWorklogId';

    /**
     * @var AliasesConfiguration
     */
    private $config;
    /**
     * @var WorklogHandler
     */
    private $worklogHandler;
    /**
     * @var Api
     */
    private $git;

    public function __construct(AliasesConfiguration $config, WorklogHandler $worklogHandler, Api $git)
    {
        $this->config = $config;
        $this->worklogHandler = $worklogHandler;
        $this->git = $git;
    }

    public function argument(InputInterface $input)
    {
        if ($input->hasArgument(self::NAME)) {
            return $this->resolve($input->getArgument(self::NAME));
        }
    }

    private function resolve($value)
    {
        $argument = IssueKeyOrWorklogId::fromString($this->config->aliasToIssueKey($value));
        if ($argument->isWorklogId()) {
            return IssueKeyOrWorklogId::fromWorklog($this->worklogHandler->retrieve($argument->worklogId()));
        }
        if ($argument->isEmpty() && $issueKey = $this->fromBranch($this->git->currentBranch())) {
            return IssueKeyOrWorklogId::fromString((string) $issueKey);
        }

        return $argument;
    }

    private function fromBranch(Branch $branch)
    {
        try {
            $issueKey = $this->config->aliasToIssueKey($branch->name());
            if ($issueKey != $branch->name()) { // has an alias for branch
                return IssueKey::fromString($issueKey);
            }
            return IssueKey::fromBranch($branch);
        } catch (MissingIssueKeyException $exception) {
            return false;
        }
    }
}
