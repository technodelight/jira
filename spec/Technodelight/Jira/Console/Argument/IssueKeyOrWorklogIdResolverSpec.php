<?php

namespace spec\Technodelight\Jira\Console\Argument;

use PhpSpec\ObjectBehavior;
use Symfony\Component\Console\Input\InputInterface;
use Technodelight\GitShell\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Console\Argument\IssueKeyOrWorklogId;
use Technodelight\Jira\Console\Argument\IssueKeyOrWorklogIdResolver;
use Technodelight\Jira\Console\Argument\IssueKeyResolver\Guesser;
use Technodelight\Jira\Domain\Worklog;

class IssueKeyOrWorklogIdResolverSpec extends ObjectBehavior
{
    const WORKLOG_ID = '12345';

    const ISSUE_KEY = 'GEN-359';

    private $worklog = [
        'id' => self::WORKLOG_ID,
        'author' => ['accountId' => 321321,'key' => 'zenc', 'name' => 'zenc', 'displayName' => 'Zenc'],
        'started' => '2017-10-02T12:34:56+0000',
        'timeSpentSeconds' => 12345,
    ];

    function let(WorklogHandler $worklogHandler, Guesser $guesser, Api $git)
    {
        $this->beConstructedWith($worklogHandler, $git, $guesser);
    }

    function it_resolves_an_input_argument(AliasesConfiguration $config, InputInterface $input)
    {
        $config->aliasToIssueKey(self::ISSUE_KEY)->willReturn(self::ISSUE_KEY);
        $input->hasArgument(IssueKeyOrWorklogIdResolver::NAME)->willReturn(true);
        $input->getArgument(IssueKeyOrWorklogIdResolver::NAME)->willReturn(self::ISSUE_KEY);

        $this->argument($input)->shouldBeLike(IssueKeyOrWorklogId::fromString(self::ISSUE_KEY));
    }

    function it_resolves_a_worklog_id_and_retrieves_issue_key(AliasesConfiguration $config, WorklogHandler $worklogHandler, InputInterface $input)
    {
        $config->aliasToIssueKey(self::WORKLOG_ID)->willReturn(self::WORKLOG_ID);

        $input->hasArgument(IssueKeyOrWorklogIdResolver::NAME)->willReturn(true);
        $input->getArgument(IssueKeyOrWorklogIdResolver::NAME)->willReturn(self::WORKLOG_ID);

        $worklog = Worklog::fromArray($this->worklog, self::ISSUE_KEY);
        $worklogHandler->retrieve(self::WORKLOG_ID)->willReturn($worklog);

        $this->argument($input)->shouldBeLike(IssueKeyOrWorklogId::fromWorklog($worklog));
    }
}
