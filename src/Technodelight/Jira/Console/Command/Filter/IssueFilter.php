<?php

namespace Technodelight\Jira\Console\Command\Filter;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Console\Command\IssueRendererAware;
use Technodelight\Jira\Template\IssueRenderer;

class IssueFilter extends AbstractCommand implements IssueRendererAware
{
    private $name;
    private $jql;

    /**
     * Constructor.
     *
     * @param string $name The name of the command; passing null means it must be set in configure()
     * @param string $jql JQL Filter to use
     *
     * @throws \LogicException When the command name is empty
     */
    public function __construct(ContainerBuilder $container, $name, $jql)
    {
        if (empty($jql)) {
            throw new \InvalidArgumentException('JQL is empty');
        }
        $this->name = $name;
        $this->jql = $jql;

        parent::__construct($container, $this->prepareCommandName($name));
    }

    private function prepareCommandName($name)
    {
        return sprintf('search:%s', $name);
    }

    protected function configure()
    {
        $this
            ->setDescription($this->descriptionFromJql())
            ->setAliases([$this->name])
            ->addOption(
                'page',
                'p',
                InputOption::VALUE_REQUIRED,
                'Page',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Api $jira */
        $jira = $this->getService('technodelight.jira.api');
        /** @var IssueRenderer $renderer */
        $renderer = $this->getService('technodelight.jira.issue_renderer');
        $issues = $jira->search($this->jql, $this->page($input), [Api::FIELDS_ALL, 'comment']);
        if (!$issues->count()) {
            $output->writeln('<info>There seem to be no results matching for your criteria.</info>');
            $output->writeln(sprintf('<fg=black>%s</>', $this->jql));
            return 0;
        }
        $renderer->renderIssues($output, $issues, $input->getOptions());
    }

    private function descriptionFromJql()
    {
        return sprintf(
            'Runs filter: \'%s\'', $this->jql
        );
    }

    private function page(InputInterface $input)
    {
        if (is_numeric($input->getOption('page'))) {
            return ($input->getOption('page') - 1) * 50;
        }
        return null;
    }
}
