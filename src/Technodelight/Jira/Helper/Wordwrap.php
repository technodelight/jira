<?php

namespace Technodelight\Jira\Helper;

use Technodelight\Jira\Console\Application;

class Wordwrap
{
    /**
     * @var \Technodelight\Jira\Console\Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function wrap($text)
    {
        $termWidth = $this->application->getTerminalDimensions()[0];
        $padding = ceil($termWidth * 0.1);
        return wordwrap($text, $termWidth - $padding);
    }
}
