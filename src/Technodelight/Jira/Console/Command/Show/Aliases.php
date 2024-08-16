<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\JiraTagConverter\Components\PrettyTable;

class Aliases extends Command
{
    public function __construct(private readonly AliasesConfiguration $aliasesConfiguration)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('show:aliases')
            ->setDescription('List all aliases')
        ;
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = $this->issueAliases();
        // add aliases to table, if any
        if (!empty($rows)) {
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

        return self::SUCCESS;
    }

    private function issueAliases(): array
    {
        $rows = [];
        foreach ($this->aliasesConfiguration->items() as $aliasConfiguration) {
            $rows[] = [$aliasConfiguration->alias(), $aliasConfiguration->issueKey()];
        }

        return $rows;
    }

    private function commandAliases(): array
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
