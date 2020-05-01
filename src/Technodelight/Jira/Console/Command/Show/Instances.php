<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration\InstancesConfiguration;
use Technodelight\JiraTagConverter\Components\PrettyTable;

class Instances extends Command
{
    private $instancesConfiguration;

    public function __construct(InstancesConfiguration $instancesConfiguration)
    {
        $this->instancesConfiguration = $instancesConfiguration;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('show:instances')
            ->setDescription('List configured instances');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (empty($this->instancesConfiguration->items())) {
            $output->writeln('No instances configured.');
            return;
        }

        $rows = $this->addInstanceRows($this->instancesConfiguration, []);

        $this->renderTable($output, $rows);
    }

    /**
     * @param InstancesConfiguration $config
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
     * @param OutputInterface $output
     * @param array $rows
     */
    protected function renderTable(OutputInterface $output, array $rows)
    {
        $table = new PrettyTable($output);
        $table
            ->setHeaders(['Name', 'Domain', 'Username'])
            ->setRows($rows);
        $table->render();
    }
}
