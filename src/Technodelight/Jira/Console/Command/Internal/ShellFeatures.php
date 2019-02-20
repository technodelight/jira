<?php

namespace Technodelight\Jira\Console\Command\Internal;

use Fuse\Fuse;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Argument\IssueKeyOrWorklogIdResolver;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;

class ShellFeatures extends Command
{
    protected function configure()
    {
        $this
            ->setName('internal:shell')
            ->setDescription('Shell-related features')
            ->setAliases(['shell'])
            ->setHelp('To use autocompletion in fish, add `complete -x -c jira -a \'(jira shell)\'` to your rc file.`')
            ->ignoreValidationErrors()
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $args = $_SERVER['argv'];

        if ($args[0] == $_SERVER['PHP_SELF']) {
            array_shift($args); // drop script name
        }
        array_shift($args); // drop first command name

        return $this->autocompletion($args, $output);
    }

    private function autocompletion($arguments, OutputInterface $output)
    {
        if (count($arguments) <= 1) {
            $command = (string) array_shift($arguments);
            $this->completeCommands($command, $output);
            return;
        } else {
            $command = array_shift($arguments);
            if ($command == 'batch') {
                $this->completeCommands(array_shift($arguments), $output);
                return;
            }
        }

        if (!$this->getApplication()->has($command)) {
            list($commands, $fuse) = $this->createCommandFuseSearch();
            $results = $fuse->search($command);
            $command = $commands[$results[0]];
        }

        $appCommand = $this->getApplication()->get($command);
        $definition = $appCommand->getDefinition();

        $index = 0;
        foreach ($arguments as $argOrOpt) {
            if (substr($argOrOpt, 0, 1) === '-') {
                // it's an option, let's fetch options
                $this->completeOption($argOrOpt, $definition, $output);
            } else {
                // it's an argument
                $this->completeArgument($argOrOpt, $index, $definition, $output);
                $index++;
            }
        }
    }

    private function completeCommands($prefix, OutputInterface $output)
    {
        list($commands, $fuse) = $this->createCommandFuseSearch();

        $this->searchWithFuse($fuse, $output, $commands, $prefix);
    }

    private function completeOption($opt, InputDefinition $definition, OutputInterface $output)
    {
        $options = [];
        foreach ($definition->getOptions() as $option) {
            if (substr($opt, 0, 2) == '--') {
                $options[] = '--' . $option->getName();
            } else {
                $options[] = '-' . $option->getShortcut();
            }
        }
        if (empty($options)) {
            return;
        }

        $fuse = new Fuse($options);
        $this->searchWithFuse($fuse, $output, $options, $opt);
    }

    private function searchWithFuse(Fuse $fuse, OutputInterface $output, $values, $prefix)
    {
        if (!empty($prefix)) {
            $results = $fuse->search($prefix);
            $matching = array_unique(array_map(function ($index) use ($values) { return $values[$index]; }, $results));
        } else {
            $matching = $values;
        }

        foreach ($matching as $match) {
            $output->writeln($match);
        }
    }

    private function completeArgument($argOrOpt, $index, InputDefinition $definition, OutputInterface $output)
    {
        $argument = $definition->getArgument($index);
        switch ($argument->getName()) {
            case IssueKeyResolver::ARGUMENT:
                $output->writeln('GEN-123'); //@TODO: search for issue
                break;
            case IssueKeyOrWorklogIdResolver::NAME:
                $output->writeln('GEN-321'); //@TODO: search for issue or worklog id
                break;
            case 'jql':
                $output->writeln('"issueKey in (GEN-123)"'); //@TODO create a jql autocomplete with fuse?
                break;
        }
    }

    /**
     * @return array
     */
    private function createCommandFuseSearch()
    {
        $commands = [];
        foreach ($this->getApplication()->all() as $command) {
            foreach ($command->getAliases() as $alias) {
                $commands[] = $alias;
            }
            $commands[] = $command->getName();
        }
        $fuse = new Fuse($commands);

        return [$commands, $fuse];
    }
}
