<?php

namespace spec\Technodelight\Jira\Console\Argument;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\GitShell\Api as Git;
use Technodelight\GitShell\Branch;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Argument\IssueKeyResolver\Guesser;
use Technodelight\Jira\Console\Argument\InteractiveIssueSelector;
use Technodelight\Jira\Domain\Issue;

class IssueKeyResolverSpec extends ObjectBehavior
{
    function let(
        Git $git,
        Guesser $guesser,
        InputInterface $input,
        OutputInterface $output,
        InteractiveIssueSelector $issueSelector,
        Issue $issue
    )
    {
        $input->hasArgument(IssueKeyResolver::ARGUMENT)->willReturn(true);
        $input->hasOption(IssueKeyResolver::OPTION)->willReturn(true);
        $input->setArgument(IssueKeyResolver::ARGUMENT, Argument::type('string'))->willReturn($input);
        $input->setOption(IssueKeyResolver::OPTION, Argument::type('string'))->willReturn($input);

        $issueSelector->chooseIssue($input, $output)->shouldNotBeCalled()->willReturn($issue);

        $this->beConstructedWith($git, $guesser, $issueSelector);
    }

    function it_resolves_issue_key_argument_from_input(Guesser $guesser, InputInterface $input, OutputInterface $output)
    {
        $guesser->guessIssueKey('PROJ-123', null)->willReturn(IssueKey::fromString('PROJ-123'));
        $input->getArgument(IssueKeyResolver::ARGUMENT)->willReturn('PROJ-123');
        $input->getArguments()->willReturn([IssueKeyResolver::ARGUMENT => 'PROJ-123']);

        $this->argument($input, $output)->shouldBeLike(IssueKey::fromString('PROJ-123'));
    }

    function it_resolves_issue_key_from_git(Guesser $guesser, InputInterface $input, OutputInterface $output, Git $git)
    {
        $resolvedIssueKey = IssueKey::fromString('PROJ-123');
        $branch = Branch::fromArray(['name' => 'feature/PROJ-123-test-branch', 'remote' => false, 'current' => true]);
        $guesser->guessIssueKey(false, $branch)->willReturn($resolvedIssueKey);
        $guesser->guessIssueKey($resolvedIssueKey, null)->willReturn($resolvedIssueKey);

        $input->getArgument(IssueKeyResolver::ARGUMENT)->willReturn(false);
        $input->getArguments()->willReturn([IssueKeyResolver::ARGUMENT => null]);

        $git->currentBranch()->willReturn($branch);

        $this->argument($input, $output)->shouldBeLike($resolvedIssueKey);
    }

    function it_can_resolve_options(Guesser $guesser, InputInterface $input, OutputInterface $output)
    {
        $guesser->guessIssueKey('PROJ-123', null)->willReturn(IssueKey::fromString('PROJ-123'));
        $input->getOption(IssueKeyResolver::OPTION)->willReturn('PROJ-123');
        $this->option($input, $output)->shouldBeLike(IssueKey::fromString('PROJ-123'));
    }

    function it_can_resolve_aliases_from_configuration(Guesser $guesser, InputInterface $input, OutputInterface $output)
    {
        $guesser->guessIssueKey('something', null)->willReturn(IssueKey::fromString('PROJ-123'));
        $guesser->guessIssueKey('PROJ-123', null)->willReturn(IssueKey::fromString('PROJ-123'));
        $input->getArguments()->willReturn([IssueKeyResolver::ARGUMENT => 'something']);
        $input->getArgument(IssueKeyResolver::ARGUMENT)->willReturn('something');
        $this->argument($input, $output)->shouldBeLike(IssueKey::fromString('PROJ-123'));
    }
}
