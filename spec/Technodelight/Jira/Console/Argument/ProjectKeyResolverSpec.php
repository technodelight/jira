<?php

namespace spec\Technodelight\Jira\Console\Argument;

use PhpSpec\ObjectBehavior;
use Symfony\Component\Console\Input\InputInterface;
use Technodelight\GitShell\Api as Git;
use Technodelight\GitShell\Branch;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Console\Argument\ProjectKey;
use Technodelight\Jira\Console\Argument\ProjectKeyResolver;

class ProjectKeyResolverSpec extends ObjectBehavior
{
    function let(Git $git, ApplicationConfiguration $configuration, InputInterface $input)
    {
        $input->hasArgument(ProjectKeyResolver::ARGUMENT)->willReturn(true);

        $this->beConstructedWith($git, $configuration);
    }

    function it_resolves_project_key_argument_from_input(InputInterface $input)
    {
        $input->getArgument(ProjectKeyResolver::ARGUMENT)->willReturn('PROJ');
        $this->argument($input)->shouldBeLike(ProjectKey::fromString('PROJ'));
    }

    function it_resolves_project_key_from_git(InputInterface $input, Git $git)
    {
        $input->getArgument(ProjectKeyResolver::ARGUMENT)->willReturn(false);
        $branch = Branch::fromArray(['name' => 'feature/PROJ-123-test-branch', 'remote' => false, 'current' => true]);
        $git->currentBranch()->willReturn($branch);

        $this->argument($input)->shouldBeLike(ProjectKey::fromString('PROJ'));
    }
}
