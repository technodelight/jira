<?php

namespace spec\Technodelight\Jira\Api\SearchQuery;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sirprize\Queried\BaseQuery;
use Technodelight\Jira\Api\SearchQuery\Condition;

class BaseQuerySpec extends ObjectBehavior
{
    function it_could_disable_conditions(Condition $condition)
    {
        $this->registerCondition('project', $condition);
        $this->activateCondition('project');
        $this->disableCondition('project');
        $this->getActiveConditions()->shouldReturn([]);
    }

    function it_could_disable_all_active_conditions(Condition $condition)
    {
        $this->registerCondition('project', $condition);
        $this->activateCondition('project');
        $this->resetActiveConditions();
        $this->getActiveConditions()->shouldReturn([]);
    }
}
