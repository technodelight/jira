<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Technodelight\Jira\Api\SymfonyConfigurationInitialiser\Initialiser;
use Technodelight\Jira\Configuration\Symfony\Configuration;

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
                'Create user-global configuration file at ~/.jira.yml'
            )
            ->addArgument(
                'sample',
                InputArgument::OPTIONAL,
                'Dump sample configuration instead of interactive init',
                false
            )
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|null|void
     * @throws \ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('sample')) {
            $this->dumpSample($input, $output);
        } else {
            $this->interactiveInit($input, $output);
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \ErrorException
     */
    protected function interactiveInit(InputInterface $input, OutputInterface $output)
    {
        $fileProvider = $this->filenameProvider();
        $init = new Initialiser;
        $config = $init->init(new Configuration, $input, $output);
        if ($input->getOption('global')) {
            $path = $fileProvider->globalFile();
        } else {
            $path = $fileProvider->localFile();
        }

        if (is_file($path)) {
            throw new \ErrorException('Config file already exists: ' . $path);
        }
        file_put_contents($path, Yaml::dump($config));
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \ErrorException
     */
    protected function dumpSample(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('global')) {
            $path = $this->filenameProvider()->globalFile() . '.sample';
        } else {
            $path = $this->filenameProvider()->localFile() . '.sample';
        }

        $this->configurationDumper()->dump($path, $input->getOption('global'));

        $output->writeLn('Sample configuration has been written to ' . $path);
    }

    /**
     * @return \Technodelight\Jira\Configuration\Symfony\FilenameProvider
     */
    private function filenameProvider()
    {
        return $this->getService('technodelight.jira.configuration.symfony.filename_provider');
    }

    /**
     * @return \Technodelight\Jira\Configuration\Symfony\ConfigurationDumper
     */
    private function configurationDumper()
    {
        return $this->getService('technodelight.jira.configuration.symfony.configuration_dumper');
    }
}
