<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Console\Application;

class CurrentInstanceProvider
{
    /**
     * @var InstancesConfiguration
     */
    private $configuration;
    /**
     * @var Application
     */
    private $app;

    public function __construct(InstancesConfiguration $configuration, Application $app)
    {
        $this->configuration = $configuration;
        $this->app = $app;
    }

    public function currentInstance()
    {
        return $this->configuration->findByName($this->app->currentInstanceName());
    }
}
