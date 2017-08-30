<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Initialise app configuration')
            ->addOption(
                'global',
                'g',
                InputOption::VALUE_NONE,
                'Generate global configuration file'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Technodelight\Jira\Configuration\Symfony\ConfigurationDumper $dumper */
        $dumper = $this->getService('technodelight.jira.configuration.symfony.configuration_dumper');
        $dialog = $this->getService('console.dialog_helper');

        if ($input->getOption('global')) {
            $path = $dumper->dumpGlobal();
        } else {
            $path = $dumper->dumpLocal();
        }
        $output->writeLn('Sample configuration has been written to ' . $path);

        $consent = $dialog->askConfirmation(
            $output,
            PHP_EOL . "<question>Do you want to open the file now? (y/N)</question>",
            false
        );
        if ($consent) {
            passthru(sprintf('open %s', escapeshellarg($path)));
        }
    }
}
