<?php

namespace Technodelight\Jira\Helper;

use Technodelight\Jira\Console\Application;

class TerminalDimensionProvider
{
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function width()
    {
        return $this->application->getTerminalDimensions()[0];
    }

    public function height()
    {
        return $this->application->getTerminalDimensions()[1];
    }
}
