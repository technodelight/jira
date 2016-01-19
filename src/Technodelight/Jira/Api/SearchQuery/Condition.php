<?php

namespace Technodelight\Jira\Api\SearchQuery;

use Sirprize\Queried\Condition\BaseCondition;
use Sirprize\Queried\Condition\Tokenizer;

class Condition extends BaseCondition
{
    const OPERATOR_AND = 'AND';
    const OPERATOR_OR = 'OR';
    const OPERATOR_ORDER_BY = 'ORDER BY';

    /**
     * Condition operator, ie. AND, OR, IS, LIKE, ~ etc.
     * @var string
     */
    protected $operator = self::OPERATOR_AND;

    public function build(Tokenizer $tokenizer = null)
    {
        $clause = $this->getClause();
        foreach ($this->getParams() as $name => $param) {
            if ($value = $this->getValue($name)) {
                $clause = strtr(
                    $clause,
                    [$this->nameToId($name) => $this->escapeValue($value)]
                );
            }
        }
        $this->setClause($clause);
    }

    public function operator($operator = null)
    {
        if (is_null($operator)) {
            return $this->operator;
        }
        $this->operator = $operator;
        return $this;
    }

    private function nameToId($name)
    {
        return sprintf(':%s', $name);
    }

    private function escapeValue($value)
    {
        if (strpos($value, '(') !== false) {
            return $value;
        }
        return sprintf('"%s"', $value);
    }
}
