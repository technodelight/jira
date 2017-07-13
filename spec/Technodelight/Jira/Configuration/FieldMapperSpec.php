<?php

namespace spec\Technodelight\Jira\Configuration;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Technodelight\Jira\Configuration\ApplicationConfiguration;

class FieldMapperSpec extends ObjectBehavior
{
    function let(ApplicationConfiguration $config)
    {
        $this->beConstructedWith($config);
    }

    function it_maps_a_field_if_it_is_mapped(ApplicationConfiguration $config)
    {
        $config->fieldMap()->willReturn(['status' => 'Status']);
        $this->map('status')->shouldReturn('Status');
    }

    function it_does_not_map_if_not_mapped(ApplicationConfiguration $config)
    {
        $config->fieldMap()->willReturn([]);
        $this->map('status')->shouldReturn('status');
    }
}
