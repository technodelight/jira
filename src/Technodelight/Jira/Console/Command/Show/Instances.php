<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration\InstancesConfiguration;
use Technodelight\JiraTagConverter\Components\PrettyTable;

class Instances extends Command
{
    public function __construct(private readonly InstancesConfiguration $instancesConf)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('show:instances')
            ->setDescription('List configured instances');
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (empty($this->instancesConf->items())) {
            $output->writeln('No instances configured.');
            return self::FAILURE;
        }

        $rows = $this->addInstanceRows($this->instancesConf);

        $this->renderTable($output, $rows);

        return self::SUCCESS;
    }

    private function addInstanceRows(InstancesConfiguration $config, array $rows = []): array
    {
        foreach ($config->items() as $instance) {
            $rows[] = [$instance->name(), $instance->domain(), $instance->password()];
        }

        return $rows;
    }

    private function renderTable(OutputInterface $output, array $rows): void
    {
        $table = new PrettyTable($output);
        $table
            ->setHeaders(['Name', 'Domain', 'Username'])
            ->setRows($rows);
        $table->render();
    }
}
