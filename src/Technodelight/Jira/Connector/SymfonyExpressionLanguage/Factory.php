<?php

namespace Technodelight\Jira\Connector\SymfonyExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Technodelight\Jira\Helper\GitBranchnameGenerator\ExpressionLanguageProvider;

class Factory
{
    /**
     * @var ExpressionLanguageProvider
     */
    private $expressionLanguageProvider;

    public function __construct(ExpressionLanguageProvider $expressionLanguageProvider)
    {
        $this->expressionLanguageProvider = $expressionLanguageProvider;
    }

    public function build()
    {
        $expressionLanguage = new ExpressionLanguage();
        $expressionLanguage->registerProvider($this->expressionLanguageProvider);

        return $expressionLanguage;
    }
}
