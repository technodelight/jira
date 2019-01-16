<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraTagConverter\Components\PrettyTable;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\InstancesConfiguration;
use Technodelight\Jira\Console\Command\AbstractCommand;

class Instances extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('show:instances')
            ->setDescription('List configured instances');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Technodelight\Jira\Configuration\ApplicationConfiguration\InstancesConfiguration $config */
        $config = $this->container->get('technodelight.jira.config.instances');
        if (empty($config->items())) {
//            $this->renderTable($output, $this->addGlobalInstanceRow($config, [], false));
            $output->writeln('No instances configured.');
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
        $table = new PrettyTable($output);
        $table
            ->setHeaders(['Name', 'Domain', 'Username'])
            ->setRows($rows);
        $table->render();
    }
}
