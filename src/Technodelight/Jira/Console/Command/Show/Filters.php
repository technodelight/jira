<?php

namespace Technodelight\Jira\Console\Command\Show;

use Fuse\Fuse;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Filter;

class Filters extends Command
{
    private Api $api;

    protected function configure(): void
    {
        $this
            ->setName('show:filters')
            ->setDescription('Show available filters')
            ->addOption(
                'search',
                's',
                InputOption::VALUE_REQUIRED,
                'Search for a filter'
            )
        ;
    }

    public function setJiraApi(Api $api): void
    {
        $this->api = $api;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $search = $input->getOption('search');
        $filters = $this->api->retrieveFilters();

        if (!empty($search)) {
            $fuse = new Fuse($filters, [
                'getFn' => function(Filter $filter, $method) {
                    return $filter->$method();
                },
                'keys' => ['name', 'description']
            ]);
            $filters = $fuse->search($search);
        }

        foreach ($filters as $filter) {
            $this->renderFilter($output, $filter);
        }

        return self::SUCCESS;
    }

    private function renderFilter(OutputInterface $output, Filter $filter): void
    {
        $output->writeln([
            sprintf(
                '%s <info>%s</info> <comment>(%s)</comment>',
                $filter->isFavourite() ? '⭐' : ' ',
                $filter->name(),
                $filter->id()
            ),
            sprintf('    <comment>owner:</comment> <info>%s</info>', $filter->owner()->displayName()),
            sprintf('    <comment>description:</comment> %s', $filter->description()),
            sprintf('    <comment>jql:</comment> %s', $filter->jql()),
            ''
        ]);
    }
}
