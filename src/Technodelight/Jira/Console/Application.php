<?php

namespace Technodelight\Jira\Console;

use Symfony\Component\Console\Application as BaseApp;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
    private $currentInstanceName = 'default';

    public function setContainer(ContainerInterface $container)
    {
        if (!isset($this->container)) {
            $this->container = $container;
        }
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function currentInstanceName()
    {
        return $this->currentInstanceName;
    }

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->handleRuntimeVariables($input);
        $this->setCatchExceptions(true);

        return parent::run($input, $output);
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (true === $input->hasParameterOption(['--debug', '-d'])) {
            $start = microtime(true);
            $startMem = memory_get_usage(true);
            $result = parent::doRun($input, $output);
            $end = microtime(true) - $start;
            $endMem = memory_get_peak_usage(true);
            $output->writeLn(sprintf('%1.4f s, mem %s', $end, $this->formatBytes($endMem - $startMem)));

            return $result;
        } elseif ($input->isInteractive() === false) {
            $batchAssistant = $this->container->get('technodelight.jira.console.batch_assistant');
            if ($issueKeys = $batchAssistant->issueKeysFromPipe()) {
                $exitCode = 0;
                $this->setAutoExit(false);
                foreach ($issueKeys as $issueKey) {
                    $inputs = $batchAssistant->prepareInput($issueKey);
                    foreach ($inputs as $input) {
                        $lastExitCode = parent::doRun($input, $output); // make sure to exit with non-zero code
                        $exitCode = max($exitCode, $lastExitCode);      // if any sub command failed
                    }
                }
                $this->setAutoExit(true);
                return $exitCode;
            }
        }

        return parent::doRun($input, $output);
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

        return sprintf('<fg=cyan>%s</>', $banner)
            . PHP_EOL . PHP_EOL
            . parent::getLongVersion() . PHP_EOL . PHP_EOL
            . 'GNU GPLv3, Copyright (c) 2015-'.date('Y').', Zsolt Gál' . PHP_EOL
            . 'See https://github.com/technodelight/jira/blob/master/LICENSE.';
    }

    protected function getDefaultInputDefinition()
    {
        $input = parent::getDefaultInputDefinition();
        $input->addOption(new InputOption('--debug', null, InputOption::VALUE_NONE, 'Enable debug mode'));
        $input->addOption(new InputOption('--instance', '-i', InputOption::VALUE_REQUIRED, 'Use an instance from config temporarily'));
        $input->addOption(new InputOption('--no-cache', '-N', InputOption::VALUE_NONE, 'Cleare app cache before running command'));

        return $input;
    }

    private function formatBytes($size, $precision = 4)
    {
        $base = log($size, 1024);

        return round(1024 ** ($base - floor($base)), $precision) . ' ' . ['', 'K', 'M', 'G', 'T'][(int) floor($base)];
    }

    /**
     * @param InputInterface $input
     * @throws \Exception
     */
    private function handleRuntimeVariables(InputInterface $input)
    {
        $this->currentInstanceName = $input->getParameterOption(['--instance', '-i']) ?: 'default';

        if (true === $input->hasParameterOption(['--no-cache', '-N'])) {
            $cache = $this->container->get('technodelight.jira.api_cache.clearer');
            $cache->clear();
            $containerCache = $this->container->get('technodelight.jira.console.di.cache_maintainer');
            $containerCache->clear();
        }
    }
}
