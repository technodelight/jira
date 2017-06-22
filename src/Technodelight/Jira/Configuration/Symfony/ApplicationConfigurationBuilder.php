<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Technodelight\Jira\Configuration\ApplicationConfiguration;

class ApplicationConfigurationBuilder
{
    private $loader;

    public function __construct(ConfigurationLoader $loader)
    {
        $this->loader = $loader;
    }

    public function build()
    {
        return ApplicationConfiguration::fromSymfonyConfigArray($this->loader->load());
    }
}
