<?php

namespace spec\Technodelight\JiraGitHubExtension\Console\Input\PullRequest\EditorInput;

use PhpSpec\ObjectBehavior;
use Technodelight\JiraGitHubExtension\Console\Input\PullRequest\EditorInput\InputAssembler;
use Technodelight\GitShell\LogEntry;

class OutputParserSpec extends ObjectBehavior
{
    function it_can_parse()
    {
        $logs = [
            LogEntry::fromArray([
                'hash' => '123',
                'message' => 'this is the content of the PR' . PHP_EOL,
                'authorName' => 'zenc',
                'authorDate' => '2019-01-22 17:49:00'
            ]),
            LogEntry::fromArray([
                'hash' => '123',
                'message' => 'trimmed title' . PHP_EOL . PHP_EOL . '- can have anything' . PHP_EOL . '- looks good',
                'authorName' => 'zenc',
                'authorDate' => '2019-01-22 17:49:00'
            ])
        ];
        $labels = [
            ['name' => 'something'],
            ['name' => 'something else']
        ];
        $milestones = [
            ['title' => 'v1.0'],
            ['title' => 'v1.1']
        ];
        $assignees = [
            ['login' => 'octocat'],
            ['login' => 'zenc'],
        ];
        $assembler = new InputAssembler('feature/GEN-123-fix-something', $logs, $labels, $milestones, $assignees);
        $content = strtr($assembler->content(), [
            '[ ] something else' => '[x] something else',
            '[ ] v1.1' => '[x] v1.1',
            '[ ] zenc' => '[x] zenc',
        ]);

        $this->beConstructedWith($content);
        $this->parse();
        $this->title()->shouldReturn('GEN-123 fix something');
        $this->content()->shouldReturn(
            '- this is the content of the PR' . PHP_EOL
            . '- can have anything' . PHP_EOL
            . '- looks good'
        );
        $this->labels()->shouldReturn(['something else']);
        $this->assignees()->shouldReturn(['zenc']);
        $this->milestone()->shouldReturn('v1.1');
    }

    function it_parses_better()
    {
        $logs = [
            LogEntry::fromArray([
                'hash' => '123',
                'message' => 'this is the content of the PR' . PHP_EOL,
                'authorName' => 'zenc',
                'authorDate' => '2019-01-22 17:49:00'
            ]),
            LogEntry::fromArray([
                'hash' => '123',
                'message' => 'trimmed title' . PHP_EOL . PHP_EOL . '- can have anything' . PHP_EOL . '- looks good',
                'authorName' => 'zenc',
                'authorDate' => '2019-01-22 17:49:00'
            ])
        ];
        $labels = [
            ['name' => 'something'],
            ['name' => 'something else']
        ];
        $milestones = [
            ['title' => 'v1.0'],
            ['title' => 'v1.1']
        ];
        $assignees = [
            ['login' => 'octocat'],
            ['login' => 'zenc']
        ];
        $assembler = new InputAssembler('feature/GEN-123-fix-something', $logs, $labels, $milestones, $assignees);
        $this->beConstructedWith($assembler->content());
        $this->parse();
        $this->title()->shouldReturn('GEN-123 fix something');
        $this->content()->shouldReturn(
            '- this is the content of the PR' . PHP_EOL
            . '- can have anything' . PHP_EOL
            . '- looks good'
        );
        $this->labels()->shouldReturn([]);
        $this->milestone()->shouldReturn(null);
        $this->assignees()->shouldReturn([]);
    }
}
