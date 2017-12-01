<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

class TransitionConfiguration
{
    /**
     * @var string
     */
    private $command;
    /**
     * @var array
     */
    private $transition;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->command = $config['command'];
        $instance->transition = $config['transition'];

        return $instance;
    }

    public function command()
    {
        return $this->command;
    }

    public function transitions()
    {
        return $this->transition;
    }

    private function __construct()
    {
    }
}
