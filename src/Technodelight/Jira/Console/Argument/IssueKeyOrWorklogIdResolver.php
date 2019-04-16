<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Technodelight\GitShell\ApiInterface as Api;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Console\Argument\IssueKeyResolver\Guesser;

class IssueKeyOrWorklogIdResolver
{
    const NAME = 'issueKeyOrWorklogId';

    /**
     * @var WorklogHandler
     */
    private $worklogHandler;
    /**
     * @var Api
     */
    private $git;
    /**
     * @var Guesser
     */
    private $guesser;

    public function __construct(WorklogHandler $worklogHandler, Api $git, Guesser $guesser)
    {
        $this->worklogHandler = $worklogHandler;
        $this->git = $git;
        $this->guesser = $guesser;
    }

    public function argument(InputInterface $input)
    {
        if ($input->hasArgument(self::NAME)) {
            return $this->resolve($input->getArgument(self::NAME));
        }
    }

    private function resolve($value)
    {
        $argument = IssueKeyOrWorklogId::fromString((string) $this->guesser->guessIssueKey($value) ?: $value);
        if ($argument->isWorklogId()) {
            return IssueKeyOrWorklogId::fromWorklog($this->worklogHandler->retrieve($argument->worklogId()));
        }
        if ($argument->isEmpty() && $issueKey = $this->guesser->guessIssueKey(null, $this->git->currentBranch())) {
            return IssueKeyOrWorklogId::fromString((string) $issueKey);
        }

        return $argument;
    }
}
