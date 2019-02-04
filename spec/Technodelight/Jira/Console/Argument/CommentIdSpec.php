<?php

namespace spec\Technodelight\Jira\Console\Argument;

use PhpSpec\ObjectBehavior;

class CommentIdSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->beConstructedThrough('fromString', ['123']);
        $this->__toString()->shouldReturn('123');
    }
}
