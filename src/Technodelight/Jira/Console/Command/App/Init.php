<?php

namespace Technodelight\Jira\Console\Command\App;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;
use Technodelight\SymfonyConfigurationInitialiser\Initialiser;
use Technodelight\Jira\Configuration\Symfony\Configuration;
use Technodelight\Jira\Console\Command\AbstractCommand;

class Init extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('app:init')
            ->setDescription('Initialise app configuration')
            ->setAliases(['init'])
            ->addOption(
                'local',
                'l',
                InputOption::VALUE_NONE,
                'Create local configuration file at current directory'
            )
            ->addOption(
                'sample',
                null,
                InputOption::VALUE_NONE,
                'Dump sample configuration instead of interactive init'
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
        if ($input->getOption('sample')) {
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
        $path = $this->configFilename($input);
        $init = new Initialiser;
        $config = $init->init(new Configuration, $input, $output);

        $output->writeln(Yaml::dump($config));
        $confirm = new ConfirmationQuestion(sprintf('Shall we save this as %s? [Yn]', $path));

        if ($this->questionHelper()->ask($input, $output, $confirm)) {
            file_put_contents($path, Yaml::dump($config));
            chmod($path, 0600);
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \ErrorException
     */
    protected function dumpSample(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('global')) {
            $path = $this->filenameProvider()->userFile() . '.sample';
        } else {
            $path = $this->filenameProvider()->projectFile() . '.sample';
        }

        $this->configurationDumper()->dump($path, false === $input->getOption('local'));

        $output->writeln('Sample configuration has been written to ' . $path);
    }

    /**
     * @param InputInterface $input
     * @return string
     * @throws \ErrorException
     */
    protected function configFilename(InputInterface $input)
    {
        $fileProvider = $this->filenameProvider();
        if ($input->getOption('local')) {
            $path = $fileProvider->projectFile();
        } else {
            $path = $fileProvider->userFile();
        }

        if (is_file($path)) {
            throw new \ErrorException('Config file already exists: ' . $path);
        }

        if (is_dir($path)) {
            throw new \ErrorException('Unexpected error: path is dir');
        }

        return $path;
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

    /**
     * @return QuestionHelper
     */
    private function questionHelper()
    {
        return $this->getService('console.question_helper');
    }
}
