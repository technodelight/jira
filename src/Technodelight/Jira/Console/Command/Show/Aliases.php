<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\JiraTagConverter\Components\PrettyTable;
use Technodelight\Jira\Console\Command\AbstractCommand;

class Aliases extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('show:aliases')
            ->setDescription('List all aliases')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($rows = $this->issueAliases()) {
            $output->writeln(['<comment>Issue Aliases:</comment>', '']);
            $table = new PrettyTable($output);
            $table->setHeaders(['Alias', 'Issue key']);
            $table->addRows($rows);
            $table->render();
            $output->writeln('');
        }

        $output->writeln(['<comment>Command Aliases:</comment>', '']);
        $table = new PrettyTable($output);
        $table->setHeaders(['Command', 'Aliases']);
        $table->addRows($this->commandAliases());
        $table->render();
    }

    /**
     * @return array
     */
    private function issueAliases()
    {
        /** @var \Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration $config */
        $config = $this->getService('technodelight.jira.config.aliases');
        $rows = [];
        foreach ($config->items() as $aliasConfiguration) {
            $rows[] = [$aliasConfiguration->alias(), $aliasConfiguration->issueKey()];
        }

        return $rows;
    }

    private function commandAliases()
    {
        $commands = $this->getApplication()->all();
        $rows = [];
        foreach ($commands as $command) {
            if ($command->isEnabled()) {
                $rows[$command->getName()] = [$command->getName(), join(', ', $command->getAliases())];
            }
        }

        ksort($rows);
        return $rows;
    }

}
