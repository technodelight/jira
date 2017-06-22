<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Helper\Table;
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
            $output->writeln('No extra instances configured.');
            return;
        }
        $rows = [];
        foreach ($config->instances() as $instance) {
            $rows[] = [$instance['name'], $instance['domain'], $instance['username']];
        }

        // use the style for this table
        $table = new Table($output);
        $table
            ->setHeaders(['Name', 'Domain', 'Username'])
            ->setRows($rows);
        $table->render();
    }

}
