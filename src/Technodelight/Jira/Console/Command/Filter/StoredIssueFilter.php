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
    public function __construct(
        private readonly Api $api,
        private readonly IssueRenderer $issueRenderer,
        private readonly FilterConfiguration $filter
    ) {
       parent::__construct();
    }

    protected function configure(): void
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filter = $this->api->retrieveFilter($this->filter->filterId())->jql();
        $issues = $this->api->search($this->filter->jql(), $this->page($input), [Api::FIELDS_ALL, 'comment']);
        if (!$issues->count()) {
            $output->writeln('<info>There seem to be no results matching for your criteria.</info>');
            $output->writeln(sprintf('<fg=black>%s</>', $this->filter->jql()));
            return self::SUCCESS;
        }
        $this->issueRenderer->renderIssues($output, $issues, $input->getOptions());

        return self::SUCCESS;
    }

    private function descriptionFromJql(): string
    {
        return sprintf(
            'Runs filter: %s\'filter %d\' (on instance %s)',
            !empty($this->filter->jql()) ? $this->filter->jql() . ' + ' : '',
            $this->filter->filterId(),
            $this->filter->instance()
        );
    }

    private function page(InputInterface $input): ?int
    {
        if (is_numeric($input->getOption('page'))) {
            return ($input->getOption('page') - 1) * 50;
        }
        return null;
    }

    private function prepareCommandName($name): string
    {
        return sprintf('search:%s', $name);
    }
}
