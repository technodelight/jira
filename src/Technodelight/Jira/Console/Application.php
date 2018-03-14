<?php

namespace Technodelight\Jira\Console;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application as BaseApp;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Technodelight\Jira\Api\JiraRestApi\Api as JiraApi;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Console\Command\Action\Issue\Assign;
use Technodelight\Jira\Console\Command\Action\Issue\Attachment;
use Technodelight\Jira\Console\Command\Action\Issue\Branch;
use Technodelight\Jira\Console\Command\Action\Issue\Comment;
use Technodelight\Jira\Console\Command\Action\Issue\LogTime;
use Technodelight\Jira\Console\Command\Action\Issue\Transition;
use Technodelight\Jira\Console\Command\App\Init;
use Technodelight\Jira\Console\Command\App\SelfUpdate;
use Technodelight\Jira\Console\Command\Filter\IssueFilter;
use Technodelight\Jira\Console\Command\Filter\Search;
use Technodelight\Jira\Console\Command\Filter\WorkInProgress;
use Technodelight\Jira\Console\Command\Internal\ShellFeatures;
use Technodelight\Jira\Console\Command\Internal\UsageStats;
use Technodelight\Jira\Console\Command\Show\Aliases;
use Technodelight\Jira\Console\Command\Show\Browse;
use Technodelight\Jira\Console\Command\Show\Dashboard;
use Technodelight\Jira\Console\Command\Show\Fields;
use Technodelight\Jira\Console\Command\Show\Instances;
use Technodelight\Jira\Console\Command\Show\Issue;
use Technodelight\Jira\Console\Command\Show\Project;
use Technodelight\Jira\Console\Command\Show\Statuses;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Helper\GitBranchnameGenerator;
use Technodelight\Jira\Helper\PluralizeHelper;
use Technodelight\Jira\Helper\TemplateHelper;

class Application extends BaseApp
{
    /**
     * Relative path to base source directory
     * @var string
     */
    private $baseDir;

    private $directories = [
        'views' => ['Resources', 'views'],
        'configs' => ['Resources', 'configs']
    ];

    /**
     * DI files to load
     * @var array
     */
    private $diFiles = [
        'helpers.xml',
        'renderers.xml',
        'api.xml',
        'console.xml',
    ];

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var ApplicationConfiguration
     */
    protected $config;

    /**
     * @var DateHelper
     */
    protected $dateHelper;

    /**
     * @var GitBranchnameGenerator
     */
    protected $gitBranchnameGenerator;

    /**
     * @var TemplateHelper
     */
    protected $templateHelper;

    /**
     * @var JiraApi
     */
    protected $jira;

    /**
     * @var bool
     */
    protected $isTesting = false;

    /**
     * Constructor.
     *
     * @param string $name    The name of the application
     * @param string $version The version of the application
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN', $isTesting = false)
    {
        $this->baseDir = $this->path([__DIR__, '..']);
        $this->isTesting = $isTesting;

        parent::__construct($name, $version);
    }

    public function addDomainCommands()
    {
        $commands = [];
        // app specific commands
        $commands[] = new ShellFeatures($this->container());
        $commands[] = new UsageStats($this->container());
        $commands[] = new Instances($this->container());
        $commands[] = new Aliases($this->container());
        // instance related commands
        $commands[] = new Fields($this->container());
        $commands[] = new Statuses($this->container());
        $commands[] = new Project($this->container());
        // issue related commands
        $commands[] = new Issue($this->container());
        $commands[] = new Browse($this->container());
        $commands[] = new LogTime($this->container());
        $commands[] = new Comment($this->container());
        $commands[] = new Assign($this->container());
        $commands[] = new Attachment($this->container());
        $commands[] = new Branch($this->container());
        foreach ($this->config()->transitions()->items() as $transition) {
            $commands[] = new Transition(
                $this->container(),
                $transition->command(),
                $transition->transitions()
            );
        }

        // issue listing commands
        $commands[] = new WorkInProgress($this->container());
        $commands[] = new Dashboard($this->container());
        $filters = $this->config()->filters();
        foreach ($filters->items() as $filter) {
            $commands[] = new IssueFilter(
                $this->container(),
                $filter->command(),
                $filter->jql()
            );
        }
        $commands[] = new Search($this->container());

        $this->addCommands($commands);
    }

    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new Init($this->container());
        $commands[] = new SelfUpdate($this->container());

        return $commands;
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->container->setParameter(
            'app.jira.debug',
            $input->getParameterOption(['--debug', '-d'])
        );
        $this->container->setParameter(
            'app.jira.instance',
            $input->getParameterOption(['--instance', '-i']) ?: 'default'
        );

        if (true === $input->hasParameterOption(['--no-cache', '-N'])) {
            /** @var \ICanBoogie\Storage\Storage $cache */
            $cache = $this->container()->get('technodelight.jira.api_cache_storage');
            $cache->clear();
        }

        if (true === $input->hasParameterOption(array('--debug', '-d'))) {

            $start = microtime(true);
            $startMem = memory_get_usage(true);
            $result = parent::doRun($input, $output);
            $end = microtime(true) - $start;
            $endMem = memory_get_peak_usage(true);
            $output->writeLn(sprintf('%1.4f s, mem %s', $end, $this->formatBytes($endMem - $startMem)));
            return $result;
        } else {
            return parent::doRun($input, $output);
        }
    }

    /**
     * @return ApplicationConfiguration
     */
    public function config()
    {
        return $this->container()->get('technodelight.jira.config');
    }

    /**
     * @return ContainerBuilder
     */
    public function container()
    {
        if (!isset($this->container)) {
            $this->container = new ContainerBuilder();
            foreach ($this->syntheticContainerServices() as $serviceId => $object) {
                $this->container->set($serviceId, $object);
            }

            $loader = new XmlFileLoader($this->container, new FileLocator($this->directory('configs')));
            foreach ($this->diFiles as $fileName) {
                $loader->load($fileName);
            }
            if ($this->isTesting) {
                $testingLoader = new XmlFileLoader($this->container, new FileLocator([$this->baseDir . '/../../../features/bootstrap/configs']));
                foreach ($this->diFiles as $fileName) {
                    if (is_file($this->baseDir . '/../../../features/bootstrap/configs/' . $fileName)) {
                        $testingLoader->load($fileName);
                    }
                }
            }

            // trigger config init for synthetic services
            $config = $this->container->get('technodelight.jira.config');
            $registrator = new ApplicationConfiguration\Service\Registrator($this->container);
            $registrator->register($config, $config->servicePrefix());
        }

        return $this->container;
    }

    /**
     * @return GitBranchnameGenerator
     */
    public function gitBranchnameGenerator()
    {
        if (!isset($this->gitBranchnameGenerator)) {
            $this->gitBranchnameGenerator = new GitBranchnameGenerator;
        }

        return $this->gitBranchnameGenerator;
    }

    public function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        $helperSet->set(new PluralizeHelper);
        return $helperSet;
    }

    /**
     * @param  string $alias
     *
     * @return string relative path
     */
    public function directory($alias)
    {
        if (!isset($this->directories[$alias])) {
            throw new \UnexpectedValueException(sprintf('No directory %s configured', $alias));
        }

        return $this->baseDir . DIRECTORY_SEPARATOR . $this->path($this->directories[$alias]);
    }

    protected function getDefaultInputDefinition()
    {
        $input = parent::getDefaultInputDefinition();
        $input->addOption(new InputOption('--debug', '-D', InputOption::VALUE_NONE, 'Enable debug mode'));
        $input->addOption(new InputOption('--instance', '-i', InputOption::VALUE_REQUIRED, 'Use an instance from config temporarily'));
        $input->addOption(new InputOption('--no-cache', '-N', InputOption::VALUE_NONE, 'Cleare app cache before running command'));
        return $input;
    }

    private function path(array $parts)
    {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    private function syntheticContainerServices()
    {
        return [
            'technodelight.jira.app' => $this,
            'app.container' => $this->container,
            'console.formatter_helper' => $this->getDefaultHelperSet()->get('formatter'),
            'console.dialog_helper' => $this->getDefaultHelperSet()->get('dialog'),
            'console.question_helper' => $this->getDefaultHelperSet()->get('question'),
        ];
    }

    private function formatBytes($size, $precision = 4)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[(int) floor($base)];
    }
}
