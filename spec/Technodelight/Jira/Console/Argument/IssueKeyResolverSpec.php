<?php

namespace spec\Technodelight\Jira\Console\Argument;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputInterface;
use Technodelight\Jira\Api\GitShell\Api as Git;
use Technodelight\Jira\Api\GitShell\Branch;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Console\Argument\Exception\MissingIssueKeyException;
use Technodelight\Jira\Console\Argument\IssueKey;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;

class IssueKeyResolverSpec extends ObjectBehavior
{
    function let(Git $git, Configuration $configuration, InputInterface $input)
    {
        $input->hasArgument(IssueKeyResolver::ARGUMENT)->willReturn(true);
        $input->hasOption(IssueKeyResolver::OPTION)->willReturn(true);

        $this->beConstructedWith($git, $configuration);
    }

    function it_resolves_issue_key_argument_from_input(InputInterface $input)
    {
        $input->getArgument(IssueKeyResolver::ARGUMENT)->willReturn('PROJ-123');
        $this->argument($input)->shouldBeLike(IssueKey::fromString('PROJ-123'));
    }

    function it_resolves_issue_key_from_git(InputInterface $input, Git $git)
    {
        $input->getArgument(IssueKeyResolver::ARGUMENT)->willReturn(false);
        $branch = Branch::fromArray(['name' => 'feature/PROJ-123-test-branch', 'remote' => false, 'current' => true]);
        $git->currentBranch()->willReturn($branch);

        $this->argument($input)->shouldBeLike(IssueKey::fromString('PROJ-123'));
    }

    function it_can_resolve_options(InputInterface $input)
    {
        $input->getOption(IssueKeyResolver::OPTION)->willReturn('PROJ-123');
        $this->option($input)->shouldBeLike(IssueKey::fromString('PROJ-123'));
    }

    function it_can_resolve_aliases_from_configuration(Configuration $configuration, InputInterface $input)
    {
        $configuration->aliases()->willReturn(['something' => 'PROJ-123']);
        $input->getArgument(IssueKeyResolver::ARGUMENT)->willReturn('something');
        $this->argument($input)->shouldBeLike(IssueKey::fromString('PROJ-123'));
    }
}
