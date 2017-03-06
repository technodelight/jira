<?php

namespace spec\Technodelight\Jira\Api\GitShell;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Technodelight\Jira\Api\GitShell\Branch;
use Technodelight\Jira\Api\GitShell\LogEntry;
use Technodelight\Jira\Api\Shell\Command;
use Technodelight\Jira\Api\Shell\Shell;

class ApiSpec extends ObjectBehavior
{
    const LOGROW = '<entry><hash><![CDATA[hash]]></hash><message><![CDATA[message]]></message><authorName>authorName</authorName><authorDate>2016-10-20 00:10:00</authorDate></entry>';
    const LOGROW2 = '<entry><hash><![CDATA[hash2]]></hash><message><![CDATA[message2]]></message><authorName>authorName2</authorName><authorDate>2016-10-20 00:10:00</authorDate></entry>';

    function let(Shell $shell)
    {
        $this->beConstructedWith($shell);
    }

    function it_renders_a_log_entry_as_object(Shell $shell)
    {
        $shell->exec(Argument::any())->shouldBeCalled()->willReturn([self::LOGROW]);
        $entry = LogEntry::fromArray(
            [
                'hash' => 'hash',
                'message' => 'message',
                'authorName' => 'authorName',
                'authorDate' => '2016-10-20 00:10:00'
            ]
        );
        $this->log('somehash')->current()->shouldBeLike($entry);
    }

    function it_renders_log_entries_as_object(Shell $shell)
    {
        $shell->exec(Argument::any())->shouldBeCalled()->willReturn([self::LOGROW, self::LOGROW2]);
        $entry = LogEntry::fromArray(
            [
                'hash' => 'hash',
                'message' => 'message',
                'authorName' => 'authorName',
                'authorDate' => '2016-10-20 00:10:00'
            ]
        );
        $this->log('somehash')->current()->shouldBeLike($entry);
        $this->log('somehash')->next()->shouldBeLike(null);
    }

    function it_can_create_and_switch_branch(Shell $shell)
    {
        $command = Command::create()->withArgument('checkout')->withOption('b')->withArgument('something');
        $shell->exec($command)->shouldBeCalled();
        $this->createBranch('something');

        $command = Command::create()->withArgument('checkout')->withArgument('something');
        $shell->exec($command)->shouldBeCalled();
        $this->switchBranch('something');
    }

    function it_lists_remotes(Shell $shell)
    {
        $command = Command::create()->withArgument('remote');
        $shell->exec($command)->shouldBeCalled()->willReturn(['origin']);
        $this->remotes()->shouldReturn(['origin']);
    }

    function it_lists_branches(Shell $shell)
    {
        $command = Command::create()->withArgument('remote');
        $shell->exec($command)->shouldBeCalled()->willReturn(['origin']);
        $this->remotes()->shouldReturn(['origin']);

        $command = Command::create()->withArgument('branch')->withOption('a');
        $shell->exec($command)->shouldBeCalled()->willReturn(['remotes/origin/feature/something', 'feature/something', '* current']);

        $branchRemote = Branch::fromArray(['name'=>'feature/something', 'remote' => 'origin', 'current' => false]);
        $branchLocal = Branch::fromArray(['name'=>'feature/something', 'remote' => '', 'current' => false]);
        $branchCurrent = Branch::fromArray(['name'=>'current', 'remote' => '', 'current' => true]);

        $res = $this->branches();
        $this->branches()->shouldBeLike([$branchRemote, $branchLocal, $branchCurrent]);
    }

    function it_finds_branches_by_pattern(Shell $shell)
    {
        $command = Command::create()->withArgument('remote');
        $shell->exec($command)->shouldBeCalled()->willReturn(['origin']);
        $this->remotes()->shouldReturn(['origin']);

        $command = Command::create()->withArgument('branch')->withOption('a')->pipe(Command::create('grep')->withArgument('\'something\''));
        $shell->exec($command)->shouldBeCalled()->willReturn(['remotes/origin/feature/something', 'feature/something']);

        $branchRemote = Branch::fromArray(['name'=>'feature/something', 'remote' => 'origin', 'current' => false]);
        $branchLocal = Branch::fromArray(['name'=>'feature/something', 'remote' => '', 'current' => false]);

        $this->branches('something')->shouldBeLike([$branchRemote, $branchLocal]);
    }

    function it_returns_the_top_level_directory_for_git(Shell $shell)
    {
        $command = Command::create()->withArgument('rev-parse')->withOption('show-toplevel');
        $shell->exec($command)->willReturn(['/somewhere/on/the/hard-drive/repo']);
        $this->topLevelDirectory()->shouldReturn('/somewhere/on/the/hard-drive/repo');
    }
}
