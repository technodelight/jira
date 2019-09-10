<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Technodelight\Jira\Configuration\ApplicationConfiguration;

class ApplicationConfigurationBuilder
{
    /**
     * @var array
     */
    private $symfonyConfigurationArray;

    public function __construct(array $symfonyConfigurationArray)
    {
        $this->symfonyConfigurationArray = $symfonyConfigurationArray;
    }

    /**
     * @return ApplicationConfiguration
     */
    public function build()
    {
        return ApplicationConfiguration::fromSymfonyConfigArray($this->symfonyConfigurationArray);
    }
}
