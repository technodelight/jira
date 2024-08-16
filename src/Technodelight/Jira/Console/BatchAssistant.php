<?php

namespace Technodelight\Jira\Console;

use Sirprize\Queried\QueryException;
use Symfony\Component\Console\Input\StringInput;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RenderersConfiguration;
use Technodelight\Jira\Console\Command\Show\Issue;

class BatchAssistant
{
    public function __construct(
        private readonly Api $jira,
        private readonly Application $app,
        private readonly RenderersConfiguration $configuration
    ) {
    }

    /** @throws QueryException */
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

    /** @throws QueryException */
    private function prefetchIssuesByKey(array $issueKeys): void
    {
        if (!empty($issueKeys)) {
            $this->jira->retrieveIssues($issueKeys);
        }
    }

    /** @SuppressWarnings(PHPMD.ElseExpression) */
    private function assembleArgumentsFromInput(string $issueKey, array $args): array
    {
        if ($args[0] == $_SERVER['PHP_SELF']) {
            array_shift($args);
        }
        if ($args[0] == 'batch') {
            array_shift($args);
        }

        // process argument placeholders
        if (in_array('+', $args)) {
            while (in_array('+', $args) === true) {
                $args[array_search('+', $args)] = $issueKey;
            }
        } elseif (in_array('.', $args)) {
            while (in_array('.', $args) === true) {
                $args[array_search('.', $args)] = $issueKey;
            }
        } elseif (in_array('{}', $args)) {
            while (in_array('{}', $args) === true) {
                $args[array_search('{}', $args)] = $issueKey;
            }
        } else {
            $args[] = $issueKey;
        }

        foreach ($args as $idx => $arg) {
            if (str_contains($arg, ' ')) {
                $args[$idx] = "'" . strtr($arg, ["'" => "\'"]) . "'";
            }
        }

        return $args;
    }

    private function glueOptionsIfRequired(array $inputArguments): array
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

    private function fetchIssueKeysFromStdIn(): array
    {
        if (function_exists('posix_isatty') && posix_isatty(STDIN) === true) {
            return [];
        }

        if (function_exists('stream_isatty') && stream_isatty(STDIN) === true) {
            return [];
        }

        $issueKeys = [];
        foreach (file('php://stdin', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES) as $row) {
            $issueKeys[] = array_filter(array_map('trim', explode(',', $row)));
        }

        return array_unique($issueKeys);
    }
}
