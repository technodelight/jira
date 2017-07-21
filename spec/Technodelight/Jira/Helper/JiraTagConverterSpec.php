<?php

namespace spec\Technodelight\Jira\Helper;

use PhpSpec\ObjectBehavior;
use Symfony\Component\Console\Output\NullOutput;

class JiraTagConverterSpec extends ObjectBehavior
{
    function let()
    {
        $output = new NullOutput();
        $this->beConstructedWith($output);
    }

    function it_does_not_convert_anything()
    {
        $this->convert('some string')->shouldReturn('some string');
    }

    function it_converts_code_block()
    {
        $this->convert('{code}something{code}')->shouldReturn('<fg=yellow>something</>');
    }

    function it_converts_colors()
    {
        $this->convert('{color:green}something{color}')->shouldReturn('<fg=green>something</>');
    }

    function it_converts_bold_and_underscore()
    {
        $this->convert('*bold*')->shouldReturn('<options=bold>bold</>');
        $this->convert('_underscore_')->shouldReturn('<options=underscore>underscore</>');
    }

    function it_converts_mentions()
    {
        $this->convert('[~technodelight]')->shouldReturn('<fg=cyan>technodelight</>');
    }

    function it_strips_panels()
    {
        $this->convert('{panel}something{panel}')->shouldReturn('something');
    }

    function it_merges_definitions()
    {
        $this->convert('{color:green}*GREEN BOLD*{color}')
             ->shouldReturn('<fg=green;options=bold>GREEN BOLD</>');
        $this->convert('{color:green}*_GREEN BOLD UNDERSCORED_*{color}')
             ->shouldReturn('<fg=green;options=bold,underscore>GREEN BOLD UNDERSCORED</>');
        $this->convert('*_BOLDUNDERSCORE_* {color:green}_GREENUNDERSCORE_{color}')
             ->shouldReturn('<options=bold,underscore>BOLDUNDERSCORE</> <fg=green;options=underscore>GREENUNDERSCORE</>');
        $this->convert('_{color:green}GREENUNDERSCORE_{color}_')
             ->shouldReturn('<fg=green;options=underscore>GREENUNDERSCORE</>');
    }

}
