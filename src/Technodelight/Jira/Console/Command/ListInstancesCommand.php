<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration;

class ListInstancesCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('instances')
            ->setDescription('List configured instances');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ApplicationConfiguration $config */
        $config = $this->container->get('technodelight.jira.config');
        if (empty($config->instances())) {
            $this->renderTable($output, $this->addGlobalInstanceRow($config, [], false));
            $output->writeln('No extra instances configured.');
            return;
        }

        $rows = $this->addInstanceRows($config, []);
        $rows = $this->addGlobalInstanceRow($config, $rows, true);
        $this->renderTable($output, $rows);
    }

    /**
     * @param ApplicationConfiguration $config
     * @param array $rows
     * @return array
     */
    protected function addInstanceRows(ApplicationConfiguration $config, array $rows)
    {
        foreach ($config->instances() as $instance) {
            $rows[] = [$instance['name'], $instance['domain'], $instance['username']];
        }

        return $rows;
    }

    /**
     * @param ApplicationConfiguration $config
     * @param array $rows
     * @param bool $withSeparator
     * @return array
     */
    protected function addGlobalInstanceRow(ApplicationConfiguration $config, array $rows, $withSeparator)
    {
        if ($withSeparator) {
            $rows[] = new TableSeparator;
        }
        $rows[] = ['global', $config->domain(), $config->username()];

        return $rows;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $rows
     */
    protected function renderTable(OutputInterface $output, $rows)
    {
        $table = new Table($output);
        $table
            ->setHeaders(['Name', 'Domain', 'Username'])
            ->setRows($rows);
        $table->render();
    }
}
