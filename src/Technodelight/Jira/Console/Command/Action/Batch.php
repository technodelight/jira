<?php

namespace Technodelight\Jira\Console\Command\Action;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RenderersConfiguration;
use Technodelight\Jira\Console\Application;
use Technodelight\Jira\Console\Command\Show\Issue;

/**
 * @deprecated will be removed in next release as replaced by (automatic) batch assistant
 */
class Batch extends Command
{
    /**
     * @var Application
     */
    private $app;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var RenderersConfiguration
     */
    private $configuration;

    public function __construct(Application $app, Api $api, RenderersConfiguration $configuration)
    {
        $this->app = $app;
        $this->api = $api;
        $this->configuration = $configuration;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('batch')
            ->setDescription('Batch any command and execute in one go. Use the plus sign ("+") to mark the place of issueKey in your command.')
            ->ignoreValidationErrors()
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKeys = file('php://stdin', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $args = $_SERVER['argv'];

        // fetch everything once then proceed from cache
        $this->api->retrieveIssues($issueKeys);

        $this->app->setAutoExit(false);
        foreach ($issueKeys as $issueKey) {
            $this->app->run(new StringInput(join(' ', $this->prepareArgs($issueKey, $args))), $output);
        }
        $this->app->setAutoExit(true);
    }

    /**
     * @param array $args
     * @return array
     */
    protected function prepareArgs($issueKey, array $args)
    {
        $inputArguments = $this->assembleArgumentsFromInput($issueKey, $args);
        return array_merge(
            $inputArguments,
            $this->glueOptionsIfRequired($inputArguments)
        );
    }

    /**
     * @param $issueKey
     * @param array $args
     * @return array
     */
    protected function assembleArgumentsFromInput($issueKey, array $args)
    {
        if ($args[0] == $_SERVER['PHP_SELF']) {
            array_shift($args);
        }
        if ($args[0] == 'batch') {
            array_shift($args);
        }

        if (in_array('+', $args)) {
            while (in_array('+', $args) === true) {
                $args[array_search('+', $args)] = $issueKey;
            }
        } else {
            array_push($args, $issueKey);
        }
        foreach ($args as $idx => $arg) {
            if (strpos($arg, ' ') !== false) {
                $args[$idx] = "'" . strtr($arg, ["'" => "\'"]) . "'";
            }
        }

        return $args;
    }

    /**
     * @param array $inputArguments
     * @return array
     */
    private function glueOptionsIfRequired(array $inputArguments)
    {
        $commandName = reset($inputArguments);
        $command = $this->app->get($commandName);
        if (!$command instanceof Issue) {
            return [];
        }

        $options = $command->getDefinition()->getOptions();
        $appendDefaultListOpt = true;
        foreach ($options as $opt) {
            if (in_array('--' . $opt->getName(), $inputArguments)
                || in_array('-'.$opt->getShortcut(), $inputArguments)) {
                $appendDefaultListOpt = false;
            }
        }

        return $appendDefaultListOpt ? ['--' . $this->configuration->preferredListRenderer()] : [];
    }
}
