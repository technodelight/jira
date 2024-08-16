<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use RuntimeException;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class TransitionsConfiguration implements RegistrableConfiguration
{
    /** @var TransitionConfiguration[] */
    private array $transitions;
    private array $config;

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    public static function fromArray(array $config): TransitionsConfiguration
    {
        $instance = new self;
        $instance->config = $config;

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

    public function commandForTransition($transitionName): string
    {
        foreach ($this->items() as $transition) {
            if (in_array($transitionName, $transition->transitions())) {
                return $transition->command();
            }
        }

        throw new RuntimeException(
            sprintf('Cannot resolve transition "%s" to a command', $transitionName)
        );
    }

    public function transitionsForCommand($command): array
    {
        foreach ($this->items() as $transition) {
            if ($transition->command() == $command) {
                return $transition->transitions();
            }
        }

        throw new RuntimeException(
            sprintf('Cannot find command "%s"', $command)
        );
    }

    public function servicePrefix(): string
    {
        return 'transitions';
    }

    /**
     * @return array
     */
    public function configAsArray(): array
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
