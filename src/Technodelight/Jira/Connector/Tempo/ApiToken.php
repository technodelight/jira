<?php

namespace Technodelight\Jira\Connector\Tempo;

use Technodelight\Jira\Configuration\ApplicationConfiguration\CurrentInstanceProvider;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\TempoConfiguration;

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
        if (empty($this->config->apiToken())) {
            return $this->config->instanceApiToken($this->instanceProvider->currentInstance()->name());
        }

        return $this->config->apiToken();
    }
}
