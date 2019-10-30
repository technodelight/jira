<?php

namespace Technodelight\Jira\Connector;

use Technodelight\Jira\Configuration\ApplicationConfiguration\CurrentInstanceProvider;

class WorklogHandlerFactory
{
    /**
     * @var CurrentInstanceProvider
     */
    private $instanceProvider;
    /**
     * @var WorklogHandler[]
     */
    private $worklogHandlers;

    public function __construct(
        CurrentInstanceProvider $instanceProvider,
        array $worklogHandlers = []
    )
    {
        $this->instanceProvider = $instanceProvider;
        $this->worklogHandlers = $worklogHandlers;
    }

    /**
     * @return WorklogHandler
     */
    public function build()
    {
        return $this->worklogHandlers[$this->instanceProvider->currentInstance()->worklogHandler()];
    }
}
