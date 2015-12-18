<?php

namespace spec\Technodelight\Jira\Api\SearchQuery;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Technodelight\Jira\Api\SearchQuery\BaseQuery;
use Technodelight\Jira\Api\SearchQuery\Condition as SearchCondition;

class BuilderSpec extends ObjectBehavior
{
    function let(BaseQuery $baseQuery)
    {
        $baseQuery->registerCondition(
            Argument::type('string'),
            Argument::type('Sirprize\Queried\Condition\BaseCondition')
        )->willReturn($baseQuery);

        $this->beConstructedWith($baseQuery);
    }

    function it_selects_a_project_and_assembles_it(BaseQuery $baseQuery, SearchCondition $projectCondition)
    {
        $projectCondition->getClause()->willReturn('project = "test"');
        $projectCondition->build()->willReturn($projectCondition);

        $baseQuery->activateCondition('project', ['project' => 'test'])->shouldBeCalled();
        $baseQuery->getActiveConditions()->willReturn(
            ['project' => $projectCondition]
        );

        $this->project('test');
        $this->assemble()->shouldReturn('project = "test"');
    }

    function it_assembles_conditions_joined_with_logical_operators(
        BaseQuery $baseQuery,
        SearchCondition $projectCondition,
        SearchCondition $statusCondition
    )
    {
        $projectCondition->getClause()->willReturn('project = "test"');
        $projectCondition->build()->willReturn($projectCondition);
        $projectCondition->operator()->willReturn(SearchCondition::OPERATOR_AND);
        $statusCondition->getClause()->willReturn('status = "Open"');
        $statusCondition->build()->willReturn($statusCondition);
        $statusCondition->operator()->willReturn(SearchCondition::OPERATOR_AND);

        $baseQuery->getActiveConditions()->willReturn(
            ['project' => $projectCondition, 'status' => $statusCondition]
        );
        $baseQuery->activateCondition('project', ['project' => 'test'])->shouldBeCalled();
        $this->project('test');
        $baseQuery->activateCondition('status', ['status' => 'Open'])->shouldBeCalled();
        $this->status('Open');

        $this->assemble()->shouldReturn('project = "test" AND status = "Open"');
    }
}
