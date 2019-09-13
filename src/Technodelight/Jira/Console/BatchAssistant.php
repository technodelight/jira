<?php

namespace Technodelight\Jira\Console;

use Symfony\Component\Console\Input\StringInput;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RenderersConfiguration;
use Technodelight\Jira\Console\Command\Show\Issue;

class BatchAssistant
{
    /**
     * @var Api
     */
    private $jira;
    /**
     * @var Application
     */
    private $app;
    /**
     * @var RenderersConfiguration
     */
    private $configuration;

    public function __construct(Api $jira, Application $app, RenderersConfiguration $configuration)
    {
        $this->jira = $jira;
        $this->app = $app;
        $this->configuration = $configuration;
    }

    public function issueKeysFromPipe(): array
    {
        $issueKeys = $this->fetchIssueKeysFromStdIn();
        if (!empty($issueKeys)) {
            $this->prefetchIssuesByKey($issueKeys);

            return $issueKeys;
        }

        return [];
    }

    /**
     * @param string $issueKey
     * @return StringInput[]
     */
    public function prepareInput(string $issueKey): array
    {
        $inputArguments = $this->assembleArgumentsFromInput($issueKey, $_SERVER['argv']);
        $commands = explode(',', array_shift($inputArguments)); //multiple commands

        $inputs = [];
        foreach ($commands as $command) {
            $args = array_merge(
                $inputArguments,
                $this->glueOptionsIfRequired([$command] + $inputArguments)
            );
            $inputs[] = new StringInput($command . ' ' . join(' ', $args));
        }

        return $inputs;
    }

    private function prefetchIssuesByKey(array $issueKeys): void
    {
        if (!empty($issueKeys)) {
            $this->jira->retrieveIssues($issueKeys);
        }
    }

    /**
     * @param string $issueKey
     * @param array $args
     * @return array
     */
    private function assembleArgumentsFromInput($issueKey, array $args)
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

    /**
     * @return array
     */
    private function fetchIssueKeysFromStdIn()
    {
        if (function_exists('posix_isatty')) {
            if (posix_isatty(STDIN) == true) {
                return [];
            }
        }
        if (function_exists('stream_isatty')) {
            if (stream_isatty(STDIN) == true) {
                return [];
            }
        }

        $issueKeys = file('php://stdin', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

        return array_unique(array_filter(array_map('trim', $issueKeys)));
    }
}
