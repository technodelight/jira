<?php

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
use Technodelight\SymfonyConfigurationInitialiser\Initialiser;

class Init extends Command
{
    const CONFIG_FILENAME = '.jira.yml';
    private $configurationDumper;
    private $git;
    private $treeBuilderFactory;
    private $questionHelper;

    public function __construct(ConfigurationDumper $configurationDumper, Api $git, TreeBuilderFactory $treeBuilderFactory, QuestionHelper $questionHelper)
    {
        $this->configurationDumper = $configurationDumper;
        $this->git = $git;
        $this->treeBuilderFactory = $treeBuilderFactory;
        $this->questionHelper = $questionHelper;

        parent::__construct();
    }

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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws ErrorException
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws ErrorException
     * @return int
     */
    private function interactiveInit(InputInterface $input, OutputInterface $output)
    {
        $path = $this->configFilePath((bool) $input->getOption('local'));

        $confirm = new ConfirmationQuestion(sprintf('Config file %s already exists. Shall we overwrite? [Yn]', $path));
        if (is_file($path) && !$this->questionHelper->ask($input, $output, $confirm)) {
            throw new ErrorException('Config file already exists: ' . $path);
        }

        $init = new Initialiser;
        $config = $init->init(new Configuration($this->treeBuilderFactory), $input, $output);

        $output->writeln(Yaml::dump($config));
        $confirm = new ConfirmationQuestion(sprintf('Shall we save this as %s? [Yn]', $path));

        if ($this->questionHelper->ask($input, $output, $confirm)) {
            file_put_contents($path, Yaml::dump($config));
            chmod($path, 0600);
            return 0;
        }

        return 1;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws ErrorException
     */
    private function dumpSample(InputInterface $input, OutputInterface $output)
    {
        $path = $this->configFilePath((bool) $input->getOption('local')) . '.sample';
        $this->configurationDumper->dump($path, false === $input->getOption('local'));

        $output->writeln('Sample configuration has been written to ' . $path);
    }

    /**
     * @param InputInterface $input
     * @return string
     * @throws ErrorException
     */
    protected function configFilename(InputInterface $input)
    {
        $fileProvider = $this->filenameProvider();
        if ($input->getOption('local')) {
            $path = '';
        } else {
            $path = $fileProvider->userFile();
        }

        if (is_file($path)) {
            throw new ErrorException('Config file already exists: ' . $path);
        }

        if (is_dir($path)) {
            throw new ErrorException('Unexpected error: path is dir');
        }

        return $path;
    }

    private function configFilePath(bool $local): string
    {
        return $local
            ? $this->git->topLevelDirectory() . '/' . self::CONFIG_FILENAME
            : getenv('HOME') . '/' . self::CONFIG_FILENAME;
    }
}
