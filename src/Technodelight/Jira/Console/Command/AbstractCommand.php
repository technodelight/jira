<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method \Technodelight\Jira\Console\Application getApplication()
 */
class AbstractCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     *
     * @throws LogicException When the command name is empty
     */
    public function __construct(ContainerInterface $container, $name = null)
    {
        $this->container = $container;
        parent::__construct($name);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return \Technodelight\Jira\Domain\Issue\IssueKey|string
     */
    public function issueKeyOption(InputInterface $input, OutputInterface $output)
    {
        return $this->issueKeyResolver()->option($input, $output);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return \Technodelight\Jira\Domain\Issue\IssueKey|string
     */
    public function issueKeyArgument(InputInterface $input, OutputInterface $output)
    {
        return $this->issueKeyResolver()->argument($input, $output);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param null $argumentName
     * @return \Technodelight\Jira\Console\Argument\Date|string
     */
    public function dateOption(InputInterface $input, $argumentName = null)
    {
        if ($argumentName) {
            return $this->dateArgumentResolver()->option($input, $argumentName);
        }
        return $this->dateArgumentResolver()->option($input);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param null $argumentName
     * @return \Technodelight\Jira\Console\Argument\Date|string
     */
    public function dateArgument(InputInterface $input, $argumentName = null)
    {
        if ($argumentName) {
            return $this->dateArgumentResolver()->argument($input, $argumentName);
        }
        return $this->dateArgumentResolver()->argument($input);
    }

    /**
     * @return \Technodelight\Jira\Console\Argument\DateResolver
     */
    private function dateArgumentResolver()
    {
        return $this->getService('technodelight.jira.console.argument.date_resolver');
    }

    /**
     * @return \Technodelight\Jira\Console\Argument\IssueKeyResolver
     */
    private function issueKeyResolver()
    {
        return $this->getService('technodelight.jira.console.argument.issue_key_resolver');
    }

    protected function getService($id)
    {
        return $this->container->get($id);
    }
}
