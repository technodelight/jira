<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @method \Technodelight\Jira\Console\Application getApplication()
 */
class AbstractCommand extends Command
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     *
     * @throws LogicException When the command name is empty
     */
    public function __construct(ContainerBuilder $container, $name = null)
    {
        $this->container = $container;
        parent::__construct($name);
    }

    public function issueKeyOption(InputInterface $input, OutputInterface $output)
    {
        return (string) $this->getService('technodelight.jira.console.argument.issue_key_resolver')->option($input, $output);
    }

    public function issueKeyArgument(InputInterface $input, OutputInterface $output)
    {
        return (string) $this->getService('technodelight.jira.console.argument.issue_key_resolver')->argument($input, $output);
    }

    public function dateOption(InputInterface $input, $argumentName = null)
    {
        if ($argumentName) {
            return (string) $this->getService('technodelight.jira.console.argument.date_resolver')->option($input, $argumentName);
        }
        return (string) $this->getService('technodelight.jira.console.argument.date_resolver')->option($input);
    }

    public function dateArgument(InputInterface $input, $argumentName = null)
    {
        if ($argumentName) {
            return (string) $this->getService('technodelight.jira.console.argument.date_resolver')->argument($input, $argumentName);
        }
        return (string) $this->getService('technodelight.jira.console.argument.date_resolver')->argument($input);
    }

    protected function getService($id)
    {
        return $this->container->get($id);
    }
}
