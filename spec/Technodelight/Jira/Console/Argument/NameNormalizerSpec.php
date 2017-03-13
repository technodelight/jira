<?php

namespace spec\Technodelight\Jira\Console\Argument;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NameNormalizerSpec extends ObjectBehavior
{
    function it_can_normalise_camel_case_names()
    {
        $this->beConstructedWith('camelCaseWord');
        $this->normalize()->shouldReturn('camel-case-word');
    }

    function it_normalises_empty_spaces()
    {
        $this->beConstructedWith('something with spaces   ');
        $this->normalize()->shouldReturn('something-with-spaces');
    }

    function it_can_normalize_weird_characters()
    {
        $this->beConstructedWith('!@£!@£@$£%t&*y:"{} something something');
        $this->normalize()->shouldReturn('t-y-something-something');
    }
}
