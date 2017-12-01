<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class TransitionsConfiguration implements RegistrableConfiguration
{
    /**
     * @var TransitionConfiguration[]
     */
    private $transitions;

    public static function fromArray(array $config)
    {
        $instance = new self;

        $instance->transitions = array_map(
            function (array $transition) {
                return TransitionConfiguration::fromArray($transition);
            },
            $config
        );

        return $instance;
    }

    public function items()
    {
        return $this->transitions;
    }

    public function commandForTransition($transitionName)
    {
        foreach ($this->items() as $transition) {
            if ($transition->transitions() == $transitionName) {
                return $transition->command();
            }
        }

        throw new \RuntimeException(
            sprintf('Cannot resolve transition "%s" to a command', $transitionName)
        );
    }

    public function transitionsForCommand($command)
    {
        foreach ($this->items() as $transition) {
            if ($transition->command() == $command) {
                return $transition->transitions();
            }
        }

        throw new \RuntimeException(
            sprintf('Cannot find command "%s"', $command)
        );
    }

    public function servicePrefix()
    {
        return 'transitions';
    }

    private function __construct()
    {
    }
}
