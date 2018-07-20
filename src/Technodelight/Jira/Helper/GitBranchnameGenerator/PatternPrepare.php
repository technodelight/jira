<?php

namespace Technodelight\Jira\Helper\GitBranchnameGenerator;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class PatternPrepare
{
    /**
     * @var ExpressionLanguage
     */
    private $expression;

    public function __construct(ExpressionLanguage $expression)
    {
        $this->expression = $expression;
    }

    /**
     * @param $string
     * @param array $data
     * @return string
     */
    public function prepare($string, array $data = [])
    {
        preg_match_all('~{[^}]+}~', $string, $matches);
        $replace = [];
        foreach ($matches[0] as $match) {
            $expression = substr($match, 1, -1);
            $replace[$match] = $this->value($expression, $data);
        }
        return strtr($string, $replace);
    }

    private function value($expression, array $data)
    {
        try {
            return $this->expression->evaluate($expression, $data);
        } catch (\Exception $e) {

        }

        return '';
    }
}
