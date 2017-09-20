<?php

namespace spec\Technodelight\Jira\Console\Argument;

use PhpSpec\ObjectBehavior;

class DateSpec extends ObjectBehavior
{
    function it_can_be_created_from_string()
    {
        $this->beConstructedFromString('today');
        $this->__toString()->shouldReturn('today');
    }

    function it_cannot_be_created_from_invalid_date_string()
    {
        $this->beConstructedFromString('nope');
        $this->shouldThrow(\InvalidArgumentException::class)
            ->duringInstantiation();
    }
}
