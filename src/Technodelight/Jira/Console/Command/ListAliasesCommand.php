<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListAliasesCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('aliases')
            ->setDescription('List all your configured issue key aliases')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($rows = $this->getAliasesTableRows()) {
            $table = new Table($output);
            $table->setHeaders(['Alias', 'Issue key']);
            $table->addRows($rows);
            $table->render();
        } else {
            $output->writeln('No issue aliases were configured yet.');
        }
    }

    /**
     * @return array
     */
    protected function getAliasesTableRows()
    {
        /** @var \Technodelight\Jira\Configuration\ApplicationConfiguration $config */
        $config = $this->getService('technodelight.jira.config');
        $rows = [];
        foreach ($config->aliases() as $alias => $issueKey) {
            $rows[] = [$alias, $issueKey];
        }

        return $rows;
    }

}
