<?php

namespace spec\Technodelight\Jira\Console\Input\PullRequest;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OutputParserSpec extends ObjectBehavior
{
    function it_can_parse()
    {
$content = <<<EOL
# INVGEN-123 pull request
INVGEN-123 fix something

this is the content of the PR

- can have anything
- looks good

#
# labels:
# [ ] something
# [x] something else
#
# milestones:
# [ ] v1.0
# [x] v1.1
EOL;

        $this->beConstructedWith($content);
        $this->parse();
        $this->title()->shouldReturn('INVGEN-123 fix something');
        $this->content()->shouldReturn(
            'this is the content of the PR' . PHP_EOL
            . PHP_EOL
            . '- can have anything' . PHP_EOL
            . '- looks good'
        );
        $this->labels()->shouldReturn(['something else']);
        $this->milestone()->shouldReturn('v1.1');
    }
}
