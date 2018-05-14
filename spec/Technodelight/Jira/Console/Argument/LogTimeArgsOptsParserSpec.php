<?php

namespace spec\Technodelight\Jira\Console\Argument;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LogTimeArgsOptsParserSpec extends ObjectBehavior
{
    private $opts = [
        'delete' => false,
        'move' => false,
        'interactive' => false,
        'keep-default-comment' => false,
        'help' => false,
        'quiet' => false,
        'verbose' => false,
        'version' => false,
        'ansi' => false,
        'no-ansi' => false,
        'no-interaction' => false,
        'debug' => false,
        'instance' => null,
        'no-cache' => false,
    ];

    function it_is_interactive_by_default()
    {
        $this->beConstructedThrough('fromArgsOpts', [
            [
                'command' => 'log',
                'issueKeyOrWorklogId' => null,
                'time' => null,
                'comment' => null,
                'date' => null,
            ],
            $this->opts
        ]);
        $this->isInteractive()->shouldReturn(true);
    }

    function it_recognises_the_only_argument_as_issueKeyOrWorklogId()
    {
        $this->beConstructedThrough('fromArgsOpts', [
            [
                'command' => 'log',
                'issueKeyOrWorklogId' => '123456',
                'time' => null,
                'comment' => null,
                'date' => null,
            ],
            $this->opts
        ]);
        $this->issueKeyOrWorklogId()->shouldReturn('123456');
        $this->time()->shouldBeNull();
    }

    function it_recognises_the_only_argument_as_time()
    {
        $this->beConstructedThrough('fromArgsOpts', [
            [
                'command' => 'log',
                'issueKeyOrWorklogId' => '1h',
                'time' => null,
                'comment' => null,
                'date' => null,
            ],
            $this->opts
        ]);
        $this->issueKeyOrWorklogId()->shouldBeNull();
        $this->time()->shouldReturn('1h');
    }

    function it_recognises_two_args_as_issueKeyOrWorklogId_and_time()
    {
        $this->beConstructedThrough('fromArgsOpts', [
            [
                'command' => 'log',
                'issueKeyOrWorklogId' => '1231235',
                'time' => '1h',
                'comment' => null,
                'date' => null,
            ],
            $this->opts
        ]);
        $this->issueKeyOrWorklogId()->shouldReturn('1231235');
        $this->time()->shouldReturn('1h');
    }

    function it_recognises_two_args_as_time_and_comment()
    {
        $this->beConstructedThrough('fromArgsOpts', [
            [
                'command' => 'log',
                'issueKeyOrWorklogId' => '1h',
                'time' => 'this is a comment',
                'comment' => null,
                'date' => null,
            ],
            $this->opts
        ]);
        $this->issueKeyOrWorklogId()->shouldBeNull();
        $this->time()->shouldReturn('1h');
        $this->comment()->shouldReturn('this is a comment');
    }

    function it_recognises_three_args_as_issueKeyWorklogId_time_comment()
    {
        $this->beConstructedThrough('fromArgsOpts', [
            [
                'command' => 'log',
                'issueKeyOrWorklogId' => 'PROJ-1',
                'time' => '1h',
                'comment' => 'this is a comment',
                'date' => null,
            ],
            $this->opts
        ]);
        $this->issueKeyOrWorklogId()->shouldReturn('PROJ-1');
        $this->time()->shouldReturn('1h');
        $this->comment()->shouldReturn('this is a comment');
    }

    function it_recognises_three_args_as_time_comment_date()
    {
        $this->beConstructedThrough('fromArgsOpts', [
            [
                'command' => 'log',
                'issueKeyOrWorklogId' => '1h',
                'time' => 'this is a comment',
                'comment' => 'yesterday',
                'date' => null,
            ],
            $this->opts
        ]);
        $this->issueKeyOrWorklogId()->shouldBeNull();
        $this->time()->shouldReturn('1h');
        $this->comment()->shouldReturn('this is a comment');
        $this->date()->shouldReturn('yesterday');
    }

    function it_recognises_three_args_as_issueKeyOrWorklogId_time_date_with_keep_comment()
    {
        $this->beConstructedThrough('fromArgsOpts', [
            [
                'command' => 'log',
                'issueKeyOrWorklogId' => '123',
                'time' => '1h',
                'comment' => 'yesterday',
                'date' => null,
            ],
            ['keep-default-comment' => true] + $this->opts
        ]);
        $this->issueKeyOrWorklogId()->shouldReturn('123');
        $this->time()->shouldReturn('1h');
        $this->comment()->shouldBeNull();
        $this->date()->shouldReturn('yesterday');
    }

    function it_recognises_three_args_as_issueKeyOrWorklogId_time_date_without_keep_comment()
    {
        $this->beConstructedThrough('fromArgsOpts', [
            [
                'command' => 'log',
                'issueKeyOrWorklogId' => '123',
                'time' => '1h',
                'comment' => 'yesterday',
                'date' => null,
            ],
            $this->opts
        ]);
        $this->issueKeyOrWorklogId()->shouldReturn('123');
        $this->time()->shouldReturn('1h');
        $this->comment()->shouldBeNull();
        $this->date()->shouldReturn('yesterday');
    }

    function it_recognises_dot_as_current_branch()
    {
        $this->beConstructedThrough('fromArgsOpts', [
            [
                'command' => 'log',
                'issueKeyOrWorklogId' => '.',
                'time' => '1h',
                'comment' => 'yesterday',
                'date' => null,
            ],
            $this->opts
        ]);
        $this->issueKeyOrWorklogId()->shouldBeNull();
        $this->time()->shouldReturn('1h');
        $this->comment()->shouldBeNull();
        $this->date()->shouldReturn('yesterday');
    }
}
