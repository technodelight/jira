<?php

namespace spec\Technodelight\Jira\Api\Shell;

use PhpSpec\ObjectBehavior;

class CommandSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Technodelight\Jira\Api\Shell\Command');
    }

    function it_can_be_created_with_argument()
    {
        $this->withArgument('log');
        $this->__toString()->shouldReturn('log');
    }

    function it_can_be_created_with_option()
    {
        $this->withOption('v');
        $this->__toString()->shouldReturn('-v');
    }

    function it_can_be_created_with_long_option()
    {
        $this->withOption('version');
        $this->__toString()->shouldReturn('--version');
    }

    function it_can_squash_options()
    {
        $this->withOption('v');
        $this->withOption('b');
        $this->squashOptions();
        $this->__toString()->shouldReturn('-vb');
    }

    function it_can_have_long_option_with_value()
    {
        $this->withOption('option', 'value');
        $this->__toString()->shouldReturn('--option=value');
    }
}
