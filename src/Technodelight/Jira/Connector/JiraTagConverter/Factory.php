<?php

namespace Technodelight\Jira\Connector\JiraTagConverter;

use Symfony\Component\Console\Terminal;
use Technodelight\Jira\Console\Application;
use Technodelight\JiraTagConverter\JiraTagConverter;

class Factory
{
    public function __construct(private readonly Terminal $terminal)
    {
    }

    public function build(array $opts = [], $setTermWidth = true)
    {
        if ($setTermWidth) {
            $opts['terminalWidth'] = $this->terminal->getWidth();
        }
        return new JiraTagConverter($opts);
    }
}
