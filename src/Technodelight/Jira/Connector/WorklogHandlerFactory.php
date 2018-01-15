<?php

namespace Technodelight\Jira\Connector;

use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\TempoConfiguration;
use Technodelight\Jira\Connector\Tempo\WorklogHandler as TempoHandler;
use Technodelight\Jira\Connector\Jira\WorklogHandler as JiraHandler;

class WorklogHandlerFactory
{
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration
     */
    private $configuration;
    /**
     * @var \Technodelight\Jira\Connector\Tempo\WorklogHandler
     */
    private $tempoHandler;
    /**
     * @var \Technodelight\Jira\Connector\Jira\WorklogHandler
     */
    private $jiraHandler;

    public function __construct(TempoConfiguration $configuration, TempoHandler $tempoHandler, JiraHandler $jiraHandler)
    {
        $this->configuration = $configuration;
        $this->tempoHandler = $tempoHandler;
        $this->jiraHandler = $jiraHandler;
    }

    /**
     * @return WorklogHandler
     */
    public function build()
    {
        if ($this->configuration->isEnabled()) {
            return $this->tempoHandler;
        }

        return $this->jiraHandler;
    }
}
