<?php

namespace Technodelight\Jira\Console\Command\Internal;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Command\AbstractCommand;

class ShellFeatures extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('internal:shell')
            ->setDescription('Shell-related features')
            ->setAliases(['shell'])
            ->setHelp('To use autocompletion in fish, add `complete -x -c jira -a \'(jira shell --auto)\'` to your rc file.`')
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
