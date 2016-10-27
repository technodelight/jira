<?php

namespace spec\Technodelight\Jira\Console\Argument;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputInterface;
use Technodelight\Jira\Api\GitShell\Branch;
use Technodelight\Jira\Console\Argument\Exception\MissingIssueKeyException;
use Technodelight\Jira\Console\Argument\IssueKey;

class IssueKeySpec extends ObjectBehavior
{
    function it_can_be_created_from_string()
    {
        $this->beConstructedFromString('PROJ-123');
        $this->__toString()->shouldReturn('PROJ-123');
    }

    function it_resolves_from_git_branchname()
    {
        $branch = Branch::fromArray(['name' => 'feature/PROJ-123-test-branch', 'remote' => false, 'current' => true]);
        $this->beConstructedFromBranch($branch);
        $this->__toString()->shouldReturn('PROJ-123');
    }

    function it_cant_be_invalid(InputInterface $input)
    {
        $this->beConstructedFromString(false);
        $this->shouldThrow(MissingIssueKeyException::class);
    }
}
