<?php

declare(strict_types=1);

namespace Technodelight\Jira\Api\JiraRestApi\SearchQuery;

use Sirprize\Queried\Condition\BaseCondition;
use Sirprize\Queried\Condition\Tokenizer;

class Condition extends BaseCondition
{
    public const OPERATOR_AND = 'AND';
    public const OPERATOR_OR = 'OR';
    public const OPERATOR_ORDER_BY = 'ORDER BY';

    protected string $operator = self::OPERATOR_AND;

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    public function build(Tokenizer $tokenizer = null): void
    {
        $clause = $this->getClause();
        foreach (array_keys($this->getParams()) as $name) {
            $value = $this->getValue($name);
            if (!empty($value)) {
                $clause = strtr(
                    $clause,
                    [$this->nameToId($name) => $this->escapeValue($value)]
                );
            }
        }
        $this->setClause($clause);
    }

    public function operator($operator = null): string|Condition
    {
        if (is_null($operator)) {
            return $this->operator;
        }
        $this->operator = $operator;
        return $this;
    }

    private function nameToId(string $name): string
    {
        return sprintf(':%s', $name);
    }

    private function escapeValue(string $value): string
    {
        if (str_contains($value, '(')) {
            return $value;
        }
        return sprintf('"%s"', $value);
    }
}
