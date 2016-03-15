<?php

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Api;

class IssueFilterCommand extends AbstractCommand
{
    /**
     * @var string
     */
    private $jql;

    /**
     * Constructor.
     *
     * @param string $name The name of the command; passing null means it must be set in configure()
     * @param string $jql JQL Filter to use
     *
     * @throws LogicException When the command name is empty
     */
    public function __construct(ContainerBuilder $container, $name, $jql)
    {
        $this->jql = $jql;
        parent::__construct($container, $name);
    }

    protected function configure()
    {
        $this->setDescription($this->descriptionFromName());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Technodelight\Api\Api $jira */
        $jira = $this->getService('technodelight.jira.api');
        /** @var Technodelight\Template\IssueRenderer $renderer */
        $renderer = $this->getService('technodelight.jira.issue_renderer');
        $renderer->setOutput($output);
        $renderer->renderIssues($jira->search($this->jql, Api::FIELDS_ALL));
    }

    private function descriptionFromName()
    {
        return ucfirst(strtr($this->name, ['-' => ' ']));
    }
}
