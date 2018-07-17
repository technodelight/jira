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
     * Returns base directory
     * (four levels up relative to this class)
     *
     * @return string
     */
    public function baseDir()
    {
        $parts = explode(DIRECTORY_SEPARATOR, __DIR__);
        $levels = count(explode('\\', get_class($this)));
        return join(DIRECTORY_SEPARATOR, array_slice($parts, 0, $levels * -1));
    }

    public function setContainer(ContainerInterface $container)
    {
        if (!isset($this->container)) {
            $this->container = $container;
        }
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->container->compile();

        if (true === $input->hasParameterOption(['--no-cache', '-N'])) {
            /** @var \ICanBoogie\Storage\Storage $cache */
            $cache = $this->container->get('technodelight.jira.api_cache_storage');
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
