<?php

namespace Technodelight\Jira\Console\Command;

use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Configuration\Symfony\Configuration;
use Technodelight\Jira\Configuration\Symfony\ConfigurationLoader;

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
        $dumper = $this->getService('technodelight.jira.configuration.symfony.configuration_dumper');
        if ($input->getOption('global')) {
            $path = $dumper->dumpGlobal();
        } else {
            $path = $dumper->dumpLocal();
        }
        $output->writeLn('Sample configuration has been written to ' . $path);
    }
}
