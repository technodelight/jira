<?php

namespace spec\Technodelight\Jira\Api\JiraRestApi\SearchQuery;

use PhpSpec\ObjectBehavior;

class ConditionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Sirprize\Queried\Condition\BaseCondition');
    }

    function it_has_a_condition_operator()
    {
        $this->operator()->shouldReturn('AND');
        $this->operator('IS')->shouldReturn($this);
        $this->operator()->shouldReturn('IS');
    }

    function it_builds_a_query_condition()
    {
        $this->setClause('project = :project');
        $this->setParams(['project' => 'project']);
        $this->addValue('project', 'test');
        $this->build();
        $this->getClause()->shouldReturn('project = "test"');
    }

    function it_does_not_escape_functions()
    {
        $this->setClause('worklogAuthor = :worklogAuthor');
        $this->setParams(['worklogAuthor' => 'worklogAuthor']);
        $this->addValue('worklogAuthor', 'currentUser()');
        $this->build();
        $this->getClause()->shouldReturn('worklogAuthor = currentUser()');
    }
}
