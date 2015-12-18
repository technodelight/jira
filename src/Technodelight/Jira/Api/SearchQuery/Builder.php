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
        'project' => 'project = :project',
        'status' => 'status = :status',
        'issueKey' => 'issueKey = :issueKey',
        'worklogDate' => 'worklogDate >= :from AND worklogDate <= :to',
        'worklogAuthor' => 'worklogAuthor = :worklogAuthor',
        'assignee' => 'assignee = :assignee',
    ];

    public function __construct(BaseQuery $baseQuery)
    {
        $this->baseQuery = $baseQuery;
        $this->registerDefaultConditions();
    }

    private function registerDefaultConditions()
    {
        foreach ($this->defaultConditions as $name => $clause) {
            $this->registerCondition($name, $clause);
        }
    }

    public static function factory()
    {
        return new self(new BaseQuery);
    }

    public function registerCondition($name, $clause)
    {
        $paramsList = $this->parseParamsListFromClause($clause);
        $this->baseQuery->registerCondition(
            $name,
            $this->createCondition($clause, $paramsList)
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

    public function status($status)
    {
        $this->baseQuery->activateCondition('status', ['status' => $status]);
    }

    public function worklogDate($from, $to)
    {
        $this->baseQuery->activateCondition(
            'worklogDate',
            ['from' => $from, 'to' => $to]
        );
    }

    public function worklogDateTo($worklogDateTo)
    {
        $this->baseQuery->activateCondition('worklogDateTo', ['worklogDateTo' => $worklogDateTo]);
    }

    public function worklogAuthor($worklogAuthor)
    {
        $this->baseQuery->activateCondition('worklogAuthor', ['worklogAuthor' => $worklogAuthor]);
    }

    public function assignee($assignee)
    {
        $this->baseQuery->activateCondition('assignee', ['assignee' => $assignee]);
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

    private function createCondition($clause, array $params = [])
    {
        $condition = new Condition();
        $condition->setClause($clause);
        if ($params) {
            $condition->setParams(array_combine($params, $params));
        }
        return $condition;
    }

}
