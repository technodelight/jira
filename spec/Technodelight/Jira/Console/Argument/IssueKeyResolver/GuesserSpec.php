<?php

namespace spec\Technodelight\Jira\Console\Argument\IssueKeyResolver;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Technodelight\GitShell\Branch;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration\BranchNameGeneratorConfiguration;
use Technodelight\Jira\Domain\Issue\IssueKey;

class GuesserSpec extends ObjectBehavior
{
    function let(AliasesConfiguration $aliasConfig, BranchNameGeneratorConfiguration $branchConfig)
    {
        $aliasConfig->aliasToIssueKey(Argument::any())->willReturnArgument(0);

        $this->beConstructedWith($aliasConfig, $branchConfig);
    }

    function it_should_guess_issueKeyFromBranchName(Branch $branch, BranchNameGeneratorConfiguration $branchConfig)
    {
        $branchConfig->patterns()->willReturn([
            'preg_match("~^Release ~", issue.summary())' => 'release/{clean(substr(issue.summary(), 8))}',
            'preg_match("~.*~", issue.summary())' => 'feature/{issueKey}-{summary}'
        ]);

        $branch->name()->willReturn('feature/GEN-123-something-i-wanted-to-fix');
        $this->guessIssueKey('something', $branch)->shouldBeLike(IssueKey::fromString('GEN-123'));
    }
}
