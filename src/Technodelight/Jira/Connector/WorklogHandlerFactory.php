<?php

namespace Technodelight\Jira\Connector;

use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Connector\Tempo\WorklogHandler as TempoHandler;

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

    public function __construct(ApplicationConfiguration $configuration, TempoHandler $tempoHandler)
    {
        $this->configuration = $configuration;
        $this->tempoHandler = $tempoHandler;
    }

    public function build()
    {
        if ($this->configuration->tempo()['enabled']) {
            return $this->tempoHandler;
        }
    }
}
