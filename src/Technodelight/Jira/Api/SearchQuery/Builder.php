<?php

namespace Technodelight\Jira\Api\SearchQuery;

use Technodelight\Jira\Api\SearchQuery\BaseQuery;
use Technodelight\Jira\Api\SearchQuery\Condition;

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

    private function registerDefaultConditions()
    {
        foreach ($this->defaultConditions as $name => $def) {
            $this->registerCondition($name, $def);
        }
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
    }

    private function parseParamsListFromClause($clause)
    {
        if (preg_match_all('~\s*:([A-Za-z]+)\s*~', $clause, $matches)) {
            return $matches[1];
        }
    }

    public function resetActiveConditions()
    {
        $this->baseQuery->resetActiveConditions();
    }

    public function project($project)
    {
        $this->baseQuery->activateCondition('project', ['project' => $project]);
    }

    public function issueKey($issueKey)
    {
        if (!is_array($issueKey)) {
            $issueKey = [$issueKey];
        }
        $this->baseQuery->activateCondition('issueKey', ['issueKeys' => join('","', $issueKey)]);
    }

    public function issueType($issueType)
    {
        if (!is_array($issueType)) {
            $issueType = [$issueType];
        }
        $this->baseQuery->activateCondition('issueType', ['issueTypes' => join('","', $issueType)]);
    }

    public function status($status)
    {
        $this->baseQuery->activateCondition('status', ['status' => $status]);
    }

    public function sprint($sprint)
    {
        $this->baseQuery->activateCondition('sprint', ['sprint' => $sprint]);
    }

    public function worklogDate($from, $to)
    {
        $this->baseQuery->activateCondition(
            'worklogDate',
            ['from' => $from, 'to' => $to]
        );
    }

    public function worklogAuthor($worklogAuthor)
    {
        $this->baseQuery->activateCondition('worklogAuthor', ['worklogAuthor' => $worklogAuthor]);
    }

    public function assignee($assignee)
    {
        $this->baseQuery->activateCondition('assignee', ['assignee' => $assignee]);
    }

    public function orderAsc($field)
    {
        $this->baseQuery->activateCondition('orderByAsc', ['field' => $field]);
    }

    public function orderDesc($field)
    {
        $this->baseQuery->activateCondition('orderByDesc', ['field' => $field]);
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

}
