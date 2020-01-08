<?php

namespace Technodelight\Jira\Connector\JiraTagConverter;

use Technodelight\Jira\Console\Application;
use Technodelight\JiraTagConverter\JiraTagConverter;

class Factory
{
    /**
     * @var Application
     */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function build(array $opts = [], $setTermWidth = true)
    {
        if ($setTermWidth) {
            list($width, ) = $this->app->getTerminalDimensions();
            $opts['terminalWidth'] = $width;
        }
        return new JiraTagConverter($opts);
    }
}
