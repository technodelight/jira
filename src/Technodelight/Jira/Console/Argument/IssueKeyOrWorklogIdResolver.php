<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Connector\WorklogHandler;

class IssueKeyOrWorklogIdResolver
{

    const NAME = 'issueKeyOrWorklogId';
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration
     */
    private $config;
    /**
     * @var \Technodelight\Jira\Connector\WorklogHandler
     */
    private $worklogHandler;

    public function __construct(AliasesConfiguration $config, WorklogHandler $worklogHandler)
    {
        $this->config = $config;
        $this->worklogHandler = $worklogHandler;
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

        return $argument;
    }
}
