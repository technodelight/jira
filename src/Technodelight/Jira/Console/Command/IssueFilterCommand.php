<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Api\Api;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Simplate;

class IssueFilterCommand extends AbstractCommand
{
    /**
     * @var string
     */
    private $jql;
    /**
     * @var array
     */
    private $issueTypeGroups;

    /**
     * Constructor.
     *
     * @param string $name The name of the command; passing null means it must be set in configure()
     * @param string $jql JQL Filter to use
     * @param array $issueTypeGroups Issue type groups for parameters
     *
     * @throws LogicException When the command name is empty
     */
    public function __construct(ContainerBuilder $container, $name, $jql, $issueTypeGroups)
    {
        $this->jql = $jql;
        $this->issueTypeGroups = $issueTypeGroups;
        parent::__construct($container, $name);
    }

    protected function configure()
    {
        $this
            ->setDescription($this->descriptionFromJql())
            ->addOption(
                'project',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Project name if differing from repo configuration'
            )
            ->addOption(
                'issueKey',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Issue key (ie. PROJ-123)'
            );

        foreach ($this->issueTypeGroups as $alias => $issueTypes) {
            $this->addOption($alias, '', InputOption::VALUE_NONE, sprintf('show %s only (%s)', $alias, join(',', $issueTypes)));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Technodelight\Jira\Api\Api $jira */
        $jira = $this->getService('technodelight.jira.api');
        /** @var Technodelight\Template\IssueRenderer $renderer */
        $renderer = $this->getService('technodelight.jira.issue_renderer');
        $renderer->setOutput($output);
        $renderer->renderIssues($jira->search($this->renderQuery($input), Api::FIELDS_ALL));
    }

    private function descriptionFromJql()
    {
        return sprintf(
            'Runs filter: \'%s\'', $this->jql
        );
    }

    private function renderQuery(InputInterface $input)
    {
        $queryTemplate = new Simplate($this->jql);
        $issueTypes = [];
        foreach ($this->issueTypeGroups as $alias => $types) {
            if ($input->getOption($alias)) {
                $issueTypes = array_merge($issueTypes, $types);
            }
        }
        $params = [
            'issueKey' => $this->issueKeyOption($input),
            'project' => $this->projectOption($input),
            'issueTypes' => sprintf('"%s"', join('","', $issueTypes)),
        ];
        foreach ($this->issueTypeGroups as $alias => $types) {
            $params[$alias] = sprintf('"%s"', join('","', $types));
        }

        return $queryTemplate->render($params);
    }
}
