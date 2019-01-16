<?php

namespace Technodelight\Jira\Connector;

use Technodelight\Jira\Configuration\ApplicationConfiguration\CurrentInstanceProvider;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\TempoConfiguration;
use Technodelight\Jira\Connector\Tempo\WorklogHandler as TempoHandler;
use Technodelight\Jira\Connector\Tempo2\WorklogHandler as Tempo2Handler;
use Technodelight\Jira\Connector\Jira\WorklogHandler as JiraHandler;

class WorklogHandlerFactory
{
    /**
     * @var CurrentInstanceProvider
     */
    private $instanceProvider;
    /**
     * @var TempoConfiguration
     */
    private $tempoConfiguration;
    /**
     * @var TempoHandler
     */
    private $tempoHandler;
    /**
     * @var Tempo2Handler
     */
    private $tempo2Handler;
    /**
     * @var JiraHandler
     */
    private $jiraHandler;

    public function __construct(
        CurrentInstanceProvider $instanceProvider,
        TempoConfiguration $tempoConfiguration,
        TempoHandler $tempoHandler,
        Tempo2Handler $tempo2Handler,
        JiraHandler $jiraHandler
    )
    {
        $this->instanceProvider = $instanceProvider;
        $this->tempoHandler = $tempoHandler;
        $this->tempo2Handler = $tempo2Handler;
        $this->jiraHandler = $jiraHandler;
        $this->tempoConfiguration = $tempoConfiguration;
    }

    /**
     * @return WorklogHandler
     */
    public function build()
    {
        if ($this->isTempoUsed()) {
            if ($this->tempoConfiguration->version() == '2') {
                return $this->tempo2Handler;
            }
            return $this->tempoHandler;
        }

        return $this->jiraHandler;
    }

    /**
     * @return bool
     */
    private function isTempoUsed()
    {
        return $this->isTempoSelectivelyEnabledForInstance()
            || $this->isTempoEnabledForEveryInstance()
            || $this->isTempoEnabledForSpecificInstances();
    }

    /**
     * @return bool
     */
    private function isTempoSelectivelyEnabledForInstance()
    {
        return $this->instanceProvider->currentInstance()->isTempoEnabled() === true;
    }

    /**
     * @return bool
     */
    private function isTempoEnabledForEveryInstance()
    {
        return $this->tempoConfiguration->isEnabled() && empty($this->tempoConfiguration->instances());
    }

    /**
     * @return bool
     */
    private function isTempoEnabledForSpecificInstances()
    {
        return $this->tempoConfiguration->isEnabled()
            || $this->tempoConfiguration->instanceIsEnabled($this->instanceProvider->currentInstance()->name());
    }
}
