<?php

namespace Technodelight\JiraTempoExtension\Connector\Tempo2;

use Technodelight\Jira\Configuration\ApplicationConfiguration\CurrentInstanceProvider;
use Technodelight\JiraTempoExtension\Configuration\TempoConfiguration;

/**
 * This class exists because the token can be resolved in runtime only, while we have to build the client during the
 * container build.
 */
class ApiToken
{
    /**
     * @var TempoConfiguration
     */
    private $config;
    /**
     * @var CurrentInstanceProvider
     */
    private $instanceProvider;

    public function __construct(TempoConfiguration $config, CurrentInstanceProvider $instanceProvider)
    {
        $this->config = $config;
        $this->instanceProvider = $instanceProvider;
    }

    public function __toString()
    {
        return $this->config->instanceApiToken($this->instanceProvider->currentInstance()->name());
    }
}
