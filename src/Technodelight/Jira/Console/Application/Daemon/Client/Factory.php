<?php

namespace Technodelight\Jira\Console\Application\Daemon\Client;

use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\DaemonConfiguration;
use Technodelight\Jira\Console\Application\Daemon\Client;

class Factory
{
    /**
     * @var DaemonConfiguration
     */
    private $config;
    /**
     * @var RuntimeConfiguration
     */
    private $runtime;

    public function __construct(DaemonConfiguration $config, RuntimeConfiguration $runtime)
    {
        $this->config = $config;
        $this->runtime = $runtime;
    }

    public function create()
    {
        return new Client(
            $this->runtime->getAddress() ?: $this->config->address(),
            $this->runtime->getPort() ?: $this->config->port()
        );
    }
}
