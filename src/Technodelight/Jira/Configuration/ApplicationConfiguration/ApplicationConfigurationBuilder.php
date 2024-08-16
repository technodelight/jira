<?php

declare(strict_types=1);

namespace Technodelight\Jira\Configuration\Symfony;

use Technodelight\Jira\Configuration\ApplicationConfiguration;

/** @SuppressWarnings(PHPMD.StaticAccess) */
class ApplicationConfigurationBuilder
{
    public function __construct(private readonly array $symfonyConfig) {}

    public function build(): ApplicationConfiguration
    {
        return ApplicationConfiguration::fromSymfonyConfigArray($this->symfonyConfig);
    }
}
