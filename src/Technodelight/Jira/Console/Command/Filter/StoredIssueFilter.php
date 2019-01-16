<?php

namespace Technodelight\Jira\Console\Command\Filter;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration\FilterConfiguration;
use Technodelight\Jira\Console\Command\IssueRendererAware;
use Technodelight\Jira\Template\IssueRenderer;

class StoredIssueFilter extends Command implements IssueRendererAware
{
    /**
     * @var FilterConfiguration
     */
    private $filter;
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
     * @param Api $api
     * @param IssueRenderer $renderer
     * @param FilterConfiguration $filter
     */
    public function __construct(Api $api, IssueRenderer $renderer, FilterConfiguration $filter)
    {
        $this->filter = $filter;
        $this->api = $api;
        $this->issueRenderer = $renderer;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName($this->prepareCommandName($this->filter->command()))
            ->setDescription($this->descriptionFromJql())
            ->setAliases([$this->filter->command()])
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
        $filter = $this->api->retrieveFilter($this->filter->filterId())->jql();
        $issues = $this->api->search($this->filter->jql(), $this->page($input), [Api::FIELDS_ALL, 'comment']);
        if (!$issues->count()) {
            $output->writeln('<info>There seem to be no results matching for your criteria.</info>');
            $output->writeln(sprintf('<fg=black>%s</>', $this->filter->jql()));
            return 0;
        }
        $this->issueRenderer->renderIssues($output, $issues, $input->getOptions());
    }

    private function descriptionFromJql()
    {
        return sprintf(
            'Runs filter: %s\'filter %d\' (on instance %s)',
            !empty($this->filter->jql()) ? $this->filter->jql() . ' + ': '',
            $this->filter->filterId(),
            $this->filter->instance()
        );
    }

    private function page(InputInterface $input)
    {
        if (is_numeric($input->getOption('page'))) {
            return ($input->getOption('page') - 1) * 50;
        }
        return null;
    }

    private function prepareCommandName($name)
    {
        return sprintf('search:%s', $name);
    }
}
