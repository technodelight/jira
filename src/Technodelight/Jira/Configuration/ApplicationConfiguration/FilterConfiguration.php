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
    /**
     * @var int
     */
    private $filterId;
    /**
     * @var string
     */
    private $instance;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->command = $config['command'];
        $instance->jql = $config['jql'];
        $instance->filterId = !empty($config['filterId']) ? $config['filterId'] : null;
        $instance->instance = !empty($config['instance']) ? $config['instance'] : null;

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

    public function filterId()
    {
        return $this->filterId;
    }

    public function instance()
    {
        return $this->instance;
    }

    private function __construct()
    {
    }
}
