<?php

namespace Technodelight\Jira\Api\JiraRestApi\SearchQuery;

use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\User;

class Builder
{
    private const DEFAULT_CONDITIONS = [
        'project' => [Condition::OPERATOR_AND, 'project = :project'],
        'status' => [Condition::OPERATOR_AND, 'status in (:status)'],
        'statusCategory' => [Condition::OPERATOR_AND, 'statusCategory in (:statusCategory)'],
        'issueKey' => [Condition::OPERATOR_AND, 'issueKey in (:issueKeys)'],
        'issueKeyInHistory' => [Condition::OPERATOR_AND, 'issueKey in issueHistory()'],
        'issueType' => [Condition::OPERATOR_AND, 'issueType in (:issueTypes)'],
        'worklogDate' => [Condition::OPERATOR_AND, 'worklogDate >= :from AND worklogDate <= :to'],
        'worklogAuthor' => [Condition::OPERATOR_AND, 'worklogAuthor = :worklogAuthor'],
        'updated' => [Condition::OPERATOR_AND, 'updated >= :from AND updated <= :to'],
        'assignee' => [Condition::OPERATOR_AND, 'assignee = :assignee'],
        'assigneeWas' => [Condition::OPERATOR_AND, 'assignee was :assignee'],
        'sprint' => [Condition::OPERATOR_AND, 'Sprint in :sprint'],
        'epicLink' => [Condition::OPERATOR_AND, '"Epic Link" = :epicLink'],
        'orderByDesc' => [Condition::OPERATOR_ORDER_BY, ':field DESC'],
        'orderByAsc' => [Condition::OPERATOR_ORDER_BY, ':field ASC'],
    ];

    public function __construct(private readonly BaseQuery $baseQuery)
    {
        $this->registerDefaultConditions();
    }

    public static function factory(): self
    {
        return new self(new BaseQuery);
    }

    public function registerCondition(string $name, array $def): self
    {
        list ($operator, $clause) = $def;
        $paramsList = $this->parseParamsListFromClause($clause);
        $this->baseQuery->registerCondition(
            $name,
            $this->createCondition($clause, $operator, $paramsList)
        );
        return $this;
    }


    public function resetActiveConditions(): self
    {
        $this->baseQuery->resetActiveConditions();
        return $this;
    }

    public function project($project): self
    {
        $this->baseQuery->activateCondition('project', ['project' => $project]);
        return $this;
    }

    /**
     * @param IssueKey|string|IssueKey[]|string[] $issueKey
     * @return $this
     * @throws \Sirprize\Queried\QueryException
     */
    public function issueKey($issueKey): self
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

    public function issueKeyInHistory(): self
    {
        $this->baseQuery->activateCondition('issueKeyInHistory');
        return $this;
    }

    public function issueType($issueType): self
    {
        if (!is_array($issueType)) {
            $issueType = [$issueType];
        }
        $this->baseQuery->activateCondition('issueType', ['issueTypes' => join('","', $issueType)]);
        return $this;
    }

    public function status($status): self
    {
        if (!is_array($status)) {
            $status = [$status];
        }
        $this->baseQuery->activateCondition('status', ['status' => '"'.join('","', $status).'"']);
        return $this;
    }

    public function statusCategory($statusCategory): self
    {
        if (!is_array($statusCategory)) {
            $statusCategory = [$statusCategory];
        }
        $condition = count($statusCategory) > 1 ? '"'.join('","', $statusCategory).'"' : $statusCategory[0];
        $this->baseQuery->activateCondition('statusCategory', ['statusCategory' => $condition]);
        return $this;
    }

    public function sprint($sprint): self
    {
        $this->baseQuery->activateCondition('sprint', ['sprint' => $sprint]);
        return $this;
    }

    public function worklogDate($from, $to): self
    {
        $this->baseQuery->activateCondition(
            'worklogDate',
            ['from' => $from, 'to' => $to]
        );
        return $this;
    }

    /** @TODO: check if this still works */
    public function worklogAuthor(User $worklogAuthor)
    {
        $this->baseQuery->activateCondition('worklogAuthor', ['worklogAuthor' => (string) $worklogAuthor->id()]);
        return $this;
    }

    public function updated($from, $to): self
    {
        $this->baseQuery->activateCondition('updated', ['from' => $from, 'to' => $to]);
        return $this;
    }

    public function assignee($assignee): self
    {
        $this->baseQuery->activateCondition('assignee', ['assignee' => $assignee]);
        return $this;
    }

    public function assigneeWas($assignee): self
    {
        $this->baseQuery->activateCondition('assigneeWas', ['assignee' => $assignee]);
        return $this;
    }

    public function epicLink(IssueKey $issueKey): self
    {
        $this->baseQuery->activateCondition('epicLink', ['epicLink' => (string)$issueKey]);
        return $this;
    }

    public function orderAsc($field): self
    {
        $this->baseQuery->activateCondition('orderByAsc', ['field' => $field]);
        return $this;
    }

    public function orderDesc($field): self
    {
        $this->baseQuery->activateCondition('orderByDesc', ['field' => $field]);
        return $this;
    }

    public function assemble(): string
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

    private function createCondition($clause, $operator, array $params = []): Condition
    {
        $condition = new Condition();
        $condition->setClause($clause);
        $condition->operator($operator);
        if ($params) {
            $condition->setParams(array_combine($params, $params));
        }
        return $condition;
    }

    private function registerDefaultConditions(): void
    {
        foreach (self::DEFAULT_CONDITIONS as $name => $def) {
            $this->registerCondition($name, $def);
        }
    }

    private function parseParamsListFromClause($clause): array
    {
        if (preg_match_all('~\s*:([A-Za-z]+)\s*~', $clause, $matches)) {
            return $matches[1];
        }
        return [];
    }
}
