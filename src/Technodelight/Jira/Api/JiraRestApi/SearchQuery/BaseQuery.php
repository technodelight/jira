<?php

namespace Technodelight\Jira\Api\JiraRestApi\SearchQuery;

use Sirprize\Queried\BaseQuery as SirprizeBaseQuery;

class BaseQuery extends SirprizeBaseQuery
{
    public function disableCondition($name)
    {
        if ($this->hasActiveCondition($name)) {
            unset($this->activeConditions[$name]);
        }
    }

    public function disableConditions(array $names)
    {
        foreach ($names as $name) {
            $this->disableCondition($name);
        }
    }

    public function resetActiveConditions()
    {
        $this->activeConditions = [];
    }
}
