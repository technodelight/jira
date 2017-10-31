<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShellCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('shell')
            ->setDescription('Shell-related features')
            ->addOption(
                'auto',
                'a',
                InputOption::VALUE_OPTIONAL,
                'Autocomplete callback',
                'all'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('auto')) {
            return $this->autocompletion($input, $output);
        }
    }

    private function autocompletion(InputInterface $input, OutputInterface $output)
    {
        if ('all' == $input->getOption('auto')) {
            $commands = $this->getApplication()->all();
            foreach ($commands as $command) {
                $output->writeln($command->getName());
            }
        } elseif ($complete = $input->getOption('auto')) {
            $command = $this->getApplication()->get($complete);
            foreach ($command->getDefinition()->getArguments() as $argument) {
                $output->writeln($argument->getName());
            }
        }
    }
}
