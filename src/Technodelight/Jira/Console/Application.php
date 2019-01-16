<?php

namespace Technodelight\Jira\Console;

use Symfony\Component\Console\Application as BaseApp;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Technodelight\Jira\Console\Application\Daemon;
use Technodelight\Jira\Helper\PluralizeHelper;

class Application extends BaseApp
{
    /**
     * @var ContainerBuilder
     */
    private $container;
    /**
     * @var string
     */
    private $daemonFilename;
    /**
     * @var bool
     */
    private $isDaemon = false;
    /**
     * @var string
     */
    private $currentInstanceName = 'default';

    public function setContainer(ContainerInterface $container)
    {
        if (!isset($this->container)) {
            $this->container = $container;
        }
    }

    public function setDaemonFilename($filename)
    {
        $this->daemonFilename = $filename;
    }

    public function currentInstanceName()
    {
        return $this->currentInstanceName;
    }

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->handleRuntimeVariables($input);

        if ($input->hasParameterOption(['--daemon', '-D'])) {
            $this->isDaemon = true;

            Daemon::setFilename(__FILE__);
            Daemon::getInstance()->setApplication($this);

            $config = $this->container->get('technodelight.jira.config.integrations.daemon');

            if ($input->hasParameterOption(['--ip'])) {
                Daemon::getInstance()->setAddress($input->getParameterOption(['--ip']));
            } else {
                Daemon::getInstance()->setAddress($config->address());
            }
            if ($input->hasParameterOption(['--port'])) {
                Daemon::getInstance()->setPort($input->getParameterOption(['--port']));
            } else {
                Daemon::getInstance()->setPort($config->port());
            }
            Daemon::getInstance()->run();
        } else {
            return parent::run($input, $output);
        }
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (true === $input->hasParameterOption(['--debug', '-d'])) {
            $start = microtime(true);
            $startMem = memory_get_usage(true);
            $result = $this->runWithClientOrApp($input, $output);
            $end = microtime(true) - $start;
            $endMem = memory_get_peak_usage(true);
            $output->writeLn(sprintf('%1.4f s, mem %s', $end, $this->formatBytes($endMem - $startMem)));

            return $result;
        } else {
            return $this->runWithClientOrApp($input, $output);
        }
    }

    public function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        $helperSet->set(new PluralizeHelper);

        return $helperSet;
    }

    public function getLongVersion()
    {
        $banner = <<<BANNER
       ___                     ___           ___
      /\  \        ___        /\  \         /\  \
      \:\  \      /\  \      /::\  \       /::\  \
  ___ /::\__\     \:\  \    /:/\:\  \     /:/\:\  \
 /\  /:/\/__/     /::\__\  /::\~\:\  \   /::\~\:\  \
 \:\/:/  /     __/:/\/__/ /:/\:\ \:\__\ /:/\:\ \:\__\
  \::/  /     /\/:/  /    \/_|::\/:/  / \/__\:\/:/  /
   \/__/      \::/__/        |:|::/  /       \::/  /
               \:\__\        |:|\/__/        /:/  /
                \/__/        |:|  |         /:/  /
                              \|__|         \/__/
BANNER;

        return sprintf('<fg=cyan>%s</>', $banner) . PHP_EOL . PHP_EOL . parent::getLongVersion();
    }

    protected function runWithClientOrApp(InputInterface $input, OutputInterface $output)
    {
        if ($this->container->get('technodelight.jira.config.integrations.daemon')->enabled() && !$this->isDaemon) {
            $this->container->get('technodelight.jira.console.daemon.client')->run($input, $output);
        } else {
            return parent::doRun($input, $output);
        }
    }

    protected function getDefaultInputDefinition()
    {
        $input = parent::getDefaultInputDefinition();
        $input->addOption(new InputOption('--debug', null, InputOption::VALUE_NONE, 'Enable debug mode'));
        $input->addOption(new InputOption('--instance', '-i', InputOption::VALUE_REQUIRED, 'Use an instance from config temporarily'));
        $input->addOption(new InputOption('--no-cache', '-N', InputOption::VALUE_NONE, 'Cleare app cache before running command'));
        $input->addOption(new InputOption('--daemon', null, InputOption::VALUE_NONE, 'Daemonise app'));
        $input->addOption(new InputOption('--port', null, InputOption::VALUE_REQUIRED, 'Port to use for daemon'));
        $input->addOption(new InputOption('--ip', null, InputOption::VALUE_REQUIRED, 'Address to use for daemon'));

        return $input;
    }

    private function formatBytes($size, $precision = 4)
    {
        $base = log($size, 1024);
        $suffixes = ['', 'K', 'M', 'G', 'T'];

        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[(int) floor($base)];
    }

    /**
     * @param InputInterface $input
     * @throws \Exception
     */
    private function handleRuntimeVariables(InputInterface $input)
    {
        $this->currentInstanceName = $input->getParameterOption(['--instance', '-i']) ?: 'default';

        $daemonRuntimeConfig = $this->container->get('technodelight.jira.console.daemon.client.runtime_configuration');
        if ($input->hasParameterOption(['--ip'])) {
            $daemonRuntimeConfig->setAddress($input->getParameterOption(['--ip']));
        }
        if ($input->hasParameterOption(['--port'])) {
            $daemonRuntimeConfig->setPort($input->getParameterOption(['--port']));
        }

        if (true === $input->hasParameterOption(['--no-cache', '-N'])) {
            /** @var \ICanBoogie\Storage\Storage $cache */
            $cache = $this->container->get('technodelight.jira.api_cache_storage');
            $cache->clear();
            $containerCache = $this->container->get('technodelight.jira.console.di.cache_maintainer');
            $containerCache->clear();
        }
    }
}
