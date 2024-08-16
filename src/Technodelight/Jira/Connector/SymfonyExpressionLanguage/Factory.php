<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\SymfonyExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Technodelight\Jira\Helper\GitBranchnameGenerator\ExpressionLanguageProvider;

class Factory
{
    public function __construct(private readonly ExpressionLanguageProvider $provider)
    {
    }

    public function build()
    {
        $expressionLanguage = new ExpressionLanguage();
        $expressionLanguage->registerProvider($this->provider);

        return $expressionLanguage;
    }
}
