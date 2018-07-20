<?php

namespace Technodelight\Jira\Helper\GitBranchnameGenerator;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var StringCleaner
     */
    private $cleaner;

    public function __construct(StringCleaner $cleaner)
    {
        $this->cleaner = $cleaner;
    }

    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions()
    {
        $cleaner = $this->cleaner;

        return [
            ExpressionFunction::fromPhp('preg_match'),
            ExpressionFunction::fromPhp('trim'),
            ExpressionFunction::fromPhp('ltrim'),
            ExpressionFunction::fromPhp('rtrim'),
            ExpressionFunction::fromPhp('substr'),
            new ExpressionFunction(
                'clean',
                function($str) {
                    return sprintf('is_string(%1$s) ? $cleaner->clean(%1$s) : %1$s', $str);
                },
                function($arguments, $str) use ($cleaner) {
                    if (!is_string($str)) {
                        return $str;
                    }

                    return $cleaner->clean($str);
                }
            ),
        ];
    }
}
