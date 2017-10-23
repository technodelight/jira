<?php

namespace spec\Technodelight\Jira\Console\Argument;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\GitShell\Api as Git;
use Technodelight\Jira\Api\GitShell\Branch;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Console\Argument\IssueKey;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Argument\InteractiveIssueSelector;

class IssueKeyResolverSpec extends ObjectBehavior
{
    function let(Git $git, ApplicationConfiguration $configuration, InputInterface $input, InteractiveIssueSelector $issueSelector)
    {
        $input->hasArgument(IssueKeyResolver::ARGUMENT)->willReturn(true);
        $input->hasOption(IssueKeyResolver::OPTION)->willReturn(true);
        $input->setArgument(IssueKeyResolver::ARGUMENT, Argument::type('string'))->willReturn($input);
        $input->setOption(IssueKeyResolver::OPTION, Argument::type('string'))->willReturn($input);

        $this->beConstructedWith($git, $configuration, $issueSelector);
    }

    function it_resolves_issue_key_argument_from_input(InputInterface $input, OutputInterface $output)
    {
        $input->getArgument(IssueKeyResolver::ARGUMENT)->willReturn('PROJ-123');
        $this->argument($input, $output)->shouldBeLike(IssueKey::fromString('PROJ-123'));
    }

    function it_resolves_issue_key_from_git(InputInterface $input, OutputInterface $output, Git $git)
    {
        $input->getArgument(IssueKeyResolver::ARGUMENT)->willReturn(false);
        $branch = Branch::fromArray(['name' => 'feature/PROJ-123-test-branch', 'remote' => false, 'current' => true]);
        $git->currentBranch()->willReturn($branch);

        $this->argument($input, $output)->shouldBeLike(IssueKey::fromString('PROJ-123'));
    }

    function it_can_resolve_options(InputInterface $input, OutputInterface $output)
    {
        $input->getOption(IssueKeyResolver::OPTION)->willReturn('PROJ-123');
        $this->option($input, $output)->shouldBeLike(IssueKey::fromString('PROJ-123'));
    }

    function it_can_resolve_aliases_from_configuration(ApplicationConfiguration $configuration, InputInterface $input, OutputInterface $output)
    {
        $configuration->aliases()->willReturn(['something' => 'PROJ-123']);
        $input->getArgument(IssueKeyResolver::ARGUMENT)->willReturn('something');
        $this->argument($input, $output)->shouldBeLike(IssueKey::fromString('PROJ-123'));
    }
}
