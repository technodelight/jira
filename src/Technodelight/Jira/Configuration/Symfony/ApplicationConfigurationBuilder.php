<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Configuration\Symfony\ApplicationConfigurationBuilder;
use Technodelight\Jira\Configuration\Symfony\ConfigurationLoader;
use Technodelight\Jira\Console\Command\InitCommand;

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
