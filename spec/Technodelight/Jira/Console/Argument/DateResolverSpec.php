<?php

namespace spec\Technodelight\Jira\Console\Argument;

use PhpSpec\ObjectBehavior;
use Symfony\Component\Console\Input\InputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration\ProjectConfiguration;
use Technodelight\Jira\Console\Argument\Date;
use Technodelight\Jira\Console\Argument\DateResolver;

class DateResolverSpec extends ObjectBehavior
{
    function let(ProjectConfiguration $configuration)
    {
        $configuration->yesterdayAsWeekday()->willReturn(false);

        $this->beConstructedWith($configuration);
    }

    function it_resolves_an_input_argument(InputInterface $input)
    {
        $input->hasArgument(DateResolver::NAME)->willReturn(true);
        $input->getArgument(DateResolver::NAME)->willReturn('today');

        $this->argument($input)->shouldBeLike(Date::fromString('today'));
    }

    function it_can_resolve_to_defaults_from_configuration(InputInterface $input, ProjectConfiguration $configuration)
    {
        $input->hasOption(DateResolver::NAME)->willReturn(true);
        $input->getOption(DateResolver::NAME)->willReturn(false);
        $configuration->defaultWorklogTimestamp()->shouldBeCalled()->willReturn('now');

        $this->option($input)->shouldBeLike(Date::fromString('now'));
    }

    function it_resolves_yesterday_according_to_configuration(InputInterface $input, ProjectConfiguration $configuration)
    {
        $referenceDate = new \DateTime('2017-02-13 12:34:56');
        $this->beConstructedWith($configuration, $referenceDate);
        $configuration->yesterdayAsWeekday()->willReturn(true);

        $input->hasArgument(DateResolver::NAME)->willReturn(true);
        $input->getArgument(DateResolver::NAME)->willReturn('yesterday');

        $this->argument($input)->shouldBeLike(Date::fromString('last weekday'));
    }
}
