<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

    public function projectOption(InputInterface $input)
    {
        $project = $this->getService('technodelight.jira.config')->project();
        if ($input->hasOption('project') && $input->getOption('project')) {
            $project = $input->getOption('project');
        }
        return $project;
    }

    public function projectArgument(InputInterface $input)
    {
        $project = $this->getService('technodelight.jira.config')->project();
        if ($input->getArgument('project')) {
            $project = $input->getArgument('project');
        }
        return $project;
    }

    public function issueKeyOption(InputInterface $input)
    {
        return (string) $this->getService('technodelight.jira.console.argument.issue_key_resolver')->option($input);
    }

    public function issueKeyArgument(InputInterface $input)
    {
        return (string) $this->getService('technodelight.jira.console.argument.issue_key_resolver')->argument($input);
    }

    protected function getService($id)
    {
        return $this->container->get($id);
    }
}
