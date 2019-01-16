<?php

namespace Technodelight\Jira\Console\Command\Filter;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Command\IssueRendererAware;
use Technodelight\Jira\Template\IssueRenderer;

class IssueFilter extends Command implements IssueRendererAware
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $jql;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var IssueRenderer
     */
    private $issueRenderer;

    /**
     * Constructor.
     *
     * @param string $name The name of the command; passing null means it must be set in configure()
     * @param string $jql JQL Filter to use
     *
     * @throws \LogicException When the command name is empty
     */
    public function __construct($name, $jql)
    {
        if (empty($jql)) {
            throw new \InvalidArgumentException('JQL is empty');
        }
        $this->name = $name;
        $this->jql = $jql;

        parent::__construct($this->prepareCommandName($name));
    }

    public function setJiraApi(Api $api)
    {
        $this->api = $api;
    }

    public function setIssueRenderer(IssueRenderer $issueRenderer)
    {
        $this->issueRenderer = $issueRenderer;
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
        $issues = $this->api->search($this->jql, $this->page($input), [Api::FIELDS_ALL, 'comment']);
        if (!$issues->count()) {
            $output->writeln('<info>There seem to be no results matching for your criteria.</info>');
            $output->writeln(sprintf('<fg=black>%s</>', $this->jql));
            return 0;
        }
        $this->issueRenderer->renderIssues($output, $issues, $input->getOptions());
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
