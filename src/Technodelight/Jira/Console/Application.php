<?php

namespace Technodelight\Jira\Console;

use Symfony\Component\Console\Application as BaseApp;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Console\Command\Action\Issue\Assign;
use Technodelight\Jira\Console\Command\Action\Issue\Attachment;
use Technodelight\Jira\Console\Command\Action\Issue\Branch;
use Technodelight\Jira\Console\Command\Action\Issue\Comment;
use Technodelight\Jira\Console\Command\Action\Issue\Link;
use Technodelight\Jira\Console\Command\Action\Issue\LogTime;
use Technodelight\Jira\Console\Command\Action\Issue\Transition;
use Technodelight\Jira\Console\Command\Action\Issue\Unlink;
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
use Technodelight\Jira\Helper\GitBranchnameGenerator;
use Technodelight\Jira\Helper\PluralizeHelper;

class Application extends BaseApp
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    public function setContainer(ContainerInterface $container)
    {
        if (!isset($this->container)) {
            $this->container = $container;
        }
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
        $commands[] = new Link($this->container());
        $commands[] = new Unlink($this->container());
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
        $this->container->compile();

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

    protected function getDefaultInputDefinition()
    {
        $input = parent::getDefaultInputDefinition();
        $input->addOption(new InputOption('--debug', '-D', InputOption::VALUE_NONE, 'Enable debug mode'));
        $input->addOption(new InputOption('--instance', '-i', InputOption::VALUE_REQUIRED, 'Use an instance from config temporarily'));
        $input->addOption(new InputOption('--no-cache', '-N', InputOption::VALUE_NONE, 'Cleare app cache before running command'));
        return $input;
    }

    private function formatBytes($size, $precision = 4)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[(int) floor($base)];
    }
}
