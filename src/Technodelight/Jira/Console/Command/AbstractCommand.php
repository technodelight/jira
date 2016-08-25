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
        $aliases = $this->getService('technodelight.jira.config')->aliases();
        $issueKey = $input->hasOption('issueKey') ? $input->getOption('issueKey') : '';
        if (isset($aliases[$issueKey])) {
            $issueKey = $aliases[$issueKey];
        }

        return $issueKey;
    }

    public function issueKeyArgument(InputInterface $input)
    {
        $aliases = $this->getService('technodelight.jira.config')->aliases();
        $issueKey = $input->getArgument('issueKey');
        if (isset($aliases[$issueKey])) {
            $issueKey = $aliases[$issueKey];
        }
        if (empty($issueKey)) {
            $git = $this->getService('technodelight.jira.git_helper');
            $issueKey = $git->issueKeyFromCurrentBranch();
            if (empty($issueKey)) {
                throw new \InvalidArgumentException('Cannot retrieve issue key from current branch nor from command line argument');
            }
        }

        return $issueKey;
    }

    protected function getService($id)
    {
        return $this->container->get($id);
    }
}
