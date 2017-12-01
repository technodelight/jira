<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\InstancesConfiguration;

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
        /** @var \Technodelight\Jira\Configuration\ApplicationConfiguration\InstancesConfiguration $config */
        $config = $this->container->get('technodelight.jira.config.instances');
        if (empty($config->items())) {
            $this->renderTable($output, $this->addGlobalInstanceRow($config, [], false));
            $output->writeln('No extra instances configured.');
            return;
        }

        $rows = $this->addInstanceRows($config, []);

        $this->renderTable($output, $rows);
    }

    /**
     * @param ApplicationConfiguration $config
     * @param array $rows
     * @return array
     */
    protected function addInstanceRows(InstancesConfiguration $config, array $rows)
    {
        foreach ($config->items() as $instance) {
            $rows[] = [$instance->name(), $instance->domain(), $instance->password()];
        }

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
