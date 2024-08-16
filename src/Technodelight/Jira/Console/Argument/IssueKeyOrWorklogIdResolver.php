<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Argument;

use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Technodelight\GitShell\ApiInterface as Api;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Console\Argument\IssueKeyResolver\Guesser;

class IssueKeyOrWorklogIdResolver
{
    public const NAME = 'issueKeyOrWorklogId';

    public function __construct(
        private readonly WorklogHandler $worklogHandler,
        private readonly Api $git,
        private readonly Guesser $guesser)
    {}

    public function argument(InputInterface $input): IssueKeyOrWorklogId
    {
        if ($input->hasArgument(self::NAME)) {
            return $this->resolve($input->getArgument(self::NAME));
        }

        throw new RuntimeException('Input does not have issue argument specified');
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function resolve(?string $value): IssueKeyOrWorklogId
    {
        $argument = IssueKeyOrWorklogId::fromString((string) $this->guesser->guessIssueKey($value) ?: $value);
        $issueKey = $this->guesser->guessIssueKey(null, $this->git->currentBranch());
        if ($argument->isWorklogId()) {
            return IssueKeyOrWorklogId::fromWorklog($this->worklogHandler->retrieve($argument->worklogId()?->id()));
        }
        if ($argument->isEmpty() && !empty($issueKey)) {
            return IssueKeyOrWorklogId::fromString((string) $issueKey);
        }

        return $argument;
    }
}
