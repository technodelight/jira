<?php

namespace Technodelight\Jira\Api\JiraRestApi\SearchQuery;

class Builder
{
    /**
     * @var BaseQuery
     */
    private $baseQuery;

    private $defaultConditions = [
        'project' => [Condition::OPERATOR_AND, 'project = :project'],
        'status' => [Condition::OPERATOR_AND, 'status = :status'],
        'issueKey' => [Condition::OPERATOR_AND, 'issueKey in (:issueKeys)'],
        'issueType' => [Condition::OPERATOR_AND, 'issueType in (:issueTypes)'],
        'worklogDate' => [Condition::OPERATOR_AND, 'worklogDate >= :from AND worklogDate <= :to'],
        'worklogAuthor' => [Condition::OPERATOR_AND, 'worklogAuthor = :worklogAuthor'],
        'assignee' => [Condition::OPERATOR_AND, 'assignee = :assignee'],
        'sprint' => [Condition::OPERATOR_AND, 'Sprint in :sprint'],
        'orderByDesc' => [Condition::OPERATOR_ORDER_BY, ':field DESC'],
        'orderByAsc' => [Condition::OPERATOR_ORDER_BY, ':field ASC'],
    ];

    public function __construct(BaseQuery $baseQuery)
    {
        $this->baseQuery = $baseQuery;
        $this->registerDefaultConditions();
    }

    public static function factory()
    {
        return new self(new BaseQuery);
    }

    public function registerCondition($name, array $def)
    {
        list ($operator, $clause) = $def;
        $paramsList = $this->parseParamsListFromClause($clause);
        $this->baseQuery->registerCondition(
            $name,
            $this->createCondition($clause, $operator, $paramsList)
        );
        return $this;
    }


    public function resetActiveConditions()
    {
        $this->baseQuery->resetActiveConditions();
        return $this;
    }

    public function project($project)
    {
        $this->baseQuery->activateCondition('project', ['project' => $project]);
        return $this;
    }

    public function issueKey($issueKey)
    {
        if (!is_array($issueKey)) {
            $issueKey = [$issueKey];
        }
        if (empty($issueKey)) {
            throw new \InvalidArgumentException('issueKey cannot be empty!');
        }
        $this->baseQuery->activateCondition('issueKey', ['issueKeys' => join('","', $issueKey)]);
        return $this;
    }

    public function issueType($issueType)
    {
        if (!is_array($issueType)) {
            $issueType = [$issueType];
        }
        $this->baseQuery->activateCondition('issueType', ['issueTypes' => join('","', $issueType)]);
        return $this;
    }

    public function status($status)
    {
        $this->baseQuery->activateCondition('status', ['status' => $status]);
        return $this;
    }

    public function sprint($sprint)
    {
        $this->baseQuery->activateCondition('sprint', ['sprint' => $sprint]);
        return $this;
    }

    public function worklogDate($from, $to)
    {
        $this->baseQuery->activateCondition(
            'worklogDate',
            ['from' => $from, 'to' => $to]
        );
        return $this;
    }

    public function worklogAuthor($worklogAuthor)
    {
        $this->baseQuery->activateCondition('worklogAuthor', ['worklogAuthor' => $worklogAuthor]);
        return $this;
    }

    public function assignee($assignee)
    {
        $this->baseQuery->activateCondition('assignee', ['assignee' => $assignee]);
        return $this;
    }

    public function orderAsc($field)
    {
        $this->baseQuery->activateCondition('orderByAsc', ['field' => $field]);
        return $this;
    }

    public function orderDesc($field)
    {
        $this->baseQuery->activateCondition('orderByDesc', ['field' => $field]);
        return $this;
    }

    public function assemble()
    {
        $index = -1;
        return join(
            ' ',
            array_map(
                function(Condition $condition) use (&$index) {
                    $index++;
                    $condition->build();
                    return trim(
                        sprintf('%s %s', $index ? $condition->operator() : '', $condition->getClause())
                    );
                },
                $this->baseQuery->getActiveConditions()
            )
        );
    }

    private function createCondition($clause, $operator, array $params = [])
    {
        $condition = new Condition();
        $condition->setClause($clause);
        $condition->operator($operator);
        if ($params) {
            $condition->setParams(array_combine($params, $params));
        }
        return $condition;
    }

    private function registerDefaultConditions()
    {
        foreach ($this->defaultConditions as $name => $def) {
            $this->registerCondition($name, $def);
        }
    }

    private function parseParamsListFromClause($clause)
    {
        if (preg_match_all('~\s*:([A-Za-z]+)\s*~', $clause, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
