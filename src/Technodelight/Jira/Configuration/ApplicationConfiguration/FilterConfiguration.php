<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

class FilterConfiguration
{
    /**
     * @var string
     */
    private $command;
    /**
     * @var string
     */
    private $jql;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->command = $config['command'];
        $instance->jql = $config['jql'];

        return $instance;
    }

    public function command()
    {
        return $this->command;
    }

    public function jql()
    {
        return $this->jql;
    }

    private function __construct()
    {
    }
}
