<?php

namespace Technodelight\Jira\Configuration;

class TransitionResolver implements \IteratorAggregate
{
    /**
     * @var array
     */
    private $transitions;

    public function __construct(array $transitions)
    {
        $this->transitions = $transitions;
    }

    public function commandForTransition($transitionName)
    {
        foreach ($this->transitions as $command => $transitions) {
            if (in_array($transitionName, $transitions)) {
                return $command;
            }
        }

        throw new \RuntimeException(
            sprintf('Cannot resolve transition "%s" to a command', $transitionName)
        );
    }

    public function transitionsForCommand($command)
    {
        if (isset($this->transitions[$command])) {
            return $this->transitions[$command];
        }

        throw new \RuntimeException(
            sprintf('Cannot find command "%s"', $command)
        );
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->transitions);
    }
}
