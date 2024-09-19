<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Command\App;

use ErrorException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;
use Technodelight\GitShell\Api;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Configuration\Configuration\TreeBuilderFactory;
use Technodelight\Jira\Connector\SymfonyConfig\ConfigurationDumper;
use Technodelight\Jira\Console\Configuration\DirectoryProvider;
use Technodelight\SymfonyConfigurationInitialiser\Initialiser;

class Init extends Command
{
    public function __construct(
        private readonly ConfigurationDumper $configurationDumper,
        private readonly DirectoryProvider $directoryProvider,
        private readonly TreeBuilderFactory $treeBuilderFactory,
        private readonly QuestionHelper $questionHelper
    ) {
        parent::__construct();
    }

    protected function configure(): void
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
//            ->addOption(
//                'sample',
//                null,
//                InputOption::VALUE_NONE,
//                'Dump sample configuration instead of interactive init'
//            )
        ;
    }

    /**
     * @throws ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
//        if ($input->getOption('sample')) {
            return $this->dumpSample($input, $output);
//        }
//
//        return $this->interactiveInit($input, $output);
    }

//    /**
//     * @SuppressWarnings(PHPMD.StaticAccess)
//     * @throws ErrorException
//     */
//    private function interactiveInit(InputInterface $input, OutputInterface $output): int
//    {
//        $path = $this->configFilePath((bool)$input->getOption('local'));
//
//        $confirm = new ConfirmationQuestion(sprintf('Config file %s already exists. Shall we overwrite? [Yn]', $path));
//        if (is_file($path) && !$this->questionHelper->ask($input, $output, $confirm)) {
//            throw new ErrorException('Config file already exists: ' . $path);
//        }
//
//        // TODO: somehow we need to interactively initialise the global config files (see config dumper)
//        $init = new Initialiser;
//        $config = $init->init(new Configuration($this->treeBuilderFactory), $input, $output);
//
//        $output->writeln(Yaml::dump($config));
//        $confirm = new ConfirmationQuestion(sprintf('Shall we save this as %s? [Yn]', $path));
//
//        if ($this->questionHelper->ask($input, $output, $confirm)) {
//            file_put_contents($path, Yaml::dump($config));
//            chmod($path, 0600);
//            return self::SUCCESS;
//        }
//
//        return self::FAILURE;
//    }

    /**
     * @throws ErrorException
     */
    private function dumpSample(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->configFilePath((bool)$input->getOption('local')) . '.sample';
        $writtenFiles = $this->configurationDumper->dump($path, false === $input->getOption('local'));

        foreach ($writtenFiles as $path) {
            $output->writeln('Sample configuration has been written to ' . $path);
        }

        return self::SUCCESS;
    }

    private function configFilePath(bool $local): string
    {
        if ($local) {
            return $this->directoryProvider->project() . DIRECTORY_SEPARATOR . '.jira.yml';
        }
        // select dotconfig as default if directory exists
        $dotConfig = $this->directoryProvider->dotConfig();
        if (is_dir($dotConfig)) {
            return $dotConfig . DIRECTORY_SEPARATOR . '.jira.yml';
        }

        return $this->directoryProvider->user() . DIRECTORY_SEPARATOR . '.jira.yml';
    }
}
