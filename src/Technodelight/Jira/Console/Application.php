<?php

namespace Technodelight\Jira\Console;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application as BaseApp;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Technodelight\Jira\Api\Api as JiraApi;
use Technodelight\Jira\Api\Client as JiraClient;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Configuration\GlobalConfiguration;
use Technodelight\Jira\Console\Command\BrowseIssueCommand;
use Technodelight\Jira\Console\Command\DashboardCommand;
use Technodelight\Jira\Console\Command\IssueFilterCommand;
use Technodelight\Jira\Console\Command\IssueTransitionCommand;
use Technodelight\Jira\Console\Command\ListWorkInProgressCommand;
use Technodelight\Jira\Console\Command\LogTimeCommand;
use Technodelight\Jira\Console\Command\SearchCommand;
use Technodelight\Jira\Console\Command\ShowCommand;
use Technodelight\Jira\Console\Command\TodoCommand;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Helper\GitBranchnameGenerator;
use Technodelight\Jira\Helper\GitHelper;
use Technodelight\Jira\Helper\HubHelper;
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
    ];

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var GitHelper
     */
    protected $gitHelper;

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
     * @var JiraClient
     */
    protected $jira;

    /**
     * Constructor.
     *
     * @param string $name    The name of the application
     * @param string $version The version of the application
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        $this->baseDir = $this->path([__DIR__, '..']);

        parent::__construct($name, $version);
    }

    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        // $commands[] = new TodoCommand($this->container());
        $commands[] = new ListWorkInProgressCommand($this->container());
        $commands[] = new LogTimeCommand($this->container());
        $commands[] = new DashboardCommand($this->container());
        $commands[] = new ShowCommand($this->container());
        $commands[] = new BrowseIssueCommand($this->container());

        foreach ($this->config()->transitions() as $alias => $transitionName) {
            $commands[] = new IssueTransitionCommand($this->container(), $alias, $transitionName);
        }
        $filters = $this->config()->filters();
        foreach ($filters as $alias => $jql) {
            $commands[] = new IssueFilterCommand($this->container(), $alias, $jql, $this->config()->issueTypeGroups());
        }
        $commands[] = new SearchCommand($this->container());

        return $commands;
    }

    /**
     * @return Configuration
     */
    public function config()
    {
        if (!isset($this->config)) {
            $git = new GitHelper;
            // init configuration
            $config = GlobalConfiguration::initFromDirectory(getenv('HOME'));
            $projectConfig = Configuration::initFromDirectory($git->topLevelDirectory());
            $config->merge($projectConfig);
            $this->config = $config;
        }

        return $this->config;
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
        }

        return $this->container;
    }

    /**
     * @return GitHelper
     */
    public function git()
    {
        if (!isset($this->gitHelper)) {
            $this->gitHelper = new GitHelper;
        }

        return $this->gitHelper;
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

    /**
     * @return TemplateHelper
     */
    public function templateHelper()
    {
        if (!isset($this->templateHelper)) {
            $this->templateHelper = new TemplateHelper;
        }

        return $this->templateHelper;
    }

    /**
     * @return DateHelper
     */
    public function dateHelper()
    {
        if (!isset($this->dateHelper)) {
            $this->dateHelper = new DateHelper;
        }

        return $this->dateHelper;
    }

    /**
     * @return JiraApi
     */
    public function jira()
    {
        if (!isset($this->jira)) {
            $client = new JiraClient($this->config());
            $this->jira = new JiraApi($client);
        }

        return $this->jira;
    }

    public function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        // $helperSet->set($this->container->get('technodelight.jira.hub_helper'));
        $helperSet->set(new GitHelper);
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

    private function path(array $parts)
    {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    private function syntheticContainerServices()
    {
        return [
            'technodelight.jira.config' => $this->config(),
            'technodelight.jira.app' => $this,
            'console.formatter_helper' => $this->getDefaultHelperSet()->get('formatter'),
            'console.dialog_helper' => $this->getDefaultHelperSet()->get('dialog'),
        ];
    }
}
