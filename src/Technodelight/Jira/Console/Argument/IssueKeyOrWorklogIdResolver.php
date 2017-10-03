<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Technodelight\Jira\Connector\WorklogHandler;

class IssueKeyOrWorklogIdResolver
{

    const NAME = 'issueKeyOrWorklogId';
    /**
     * @var \Technodelight\Jira\Connector\WorklogHandler
     */
    private $worklogHandler;

    public function __construct(WorklogHandler $worklogHandler)
    {
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
        $argument = IssueKeyOrWorklogId::fromString($value);
        if ($argument->isWorklogId()) {
            return IssueKeyOrWorklogId::fromWorklog($this->worklogHandler->retrieve($argument->worklogId()));
        }
        return $argument;
    }
}
