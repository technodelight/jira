<?php

namespace Technodelight\Jira\Console\Command\Internal;

use Fuse\Fuse;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\InstancesConfiguration;
use Technodelight\Jira\Console\Application;
use Technodelight\Jira\Console\Argument\InteractiveIssueSelector;
use Technodelight\Jira\Console\Argument\IssueKeyOrWorklogIdResolver;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\SecondsToNone;

/**
 * @method Application getApplication()
 */
class ShellFeatures extends Command
{
    /**
     * @var InteractiveIssueSelector
     */
    private $issueSelector;
    /**
     * @var AliasesConfiguration
     */
    private $aliasesConfiguration;
    /**
     * @var SecondsToNone
     */
    private $secondsConverter;
    /**
     * @var InstancesConfiguration
     */
    private $instancesConfiguration;

    public function __construct(
        $name = null,
        InteractiveIssueSelector $issueSelector = null,
        AliasesConfiguration $aliasesConfiguration = null,
        SecondsToNone $secondsConverter = null,
        InstancesConfiguration $instancesConfiguration = null
    )
    {
        $this->issueSelector = $issueSelector;
        $this->aliasesConfiguration = $aliasesConfiguration;
        $this->secondsConverter = $secondsConverter;
        $this->instancesConfiguration = $instancesConfiguration;

        parent::__construct();
    }

    /**
     * @return InteractiveIssueSelector
     */
    private function getIssueSelector(): InteractiveIssueSelector
    {
        return $this->issueSelector
            ?: $this->getApplication()->getContainer()->get('technodelight.jira.console.interactive_issue_selector');
    }

    private function getAliasesConfiguration(): AliasesConfiguration
    {
        return $this->aliasesConfiguration
            ?: $this->getApplication()->getContainer()->get('technodelight.jira.config.aliases');
    }

    private function getInstancesConfiguraiton(): InstancesConfiguration
    {
        return $this->instancesConfiguration
            ?: $this->getApplication()->getContainer()->get('technodelight.jira.config.instances');
    }

    private function getSecondsConverter(): SecondsToNone
    {
        return $this->secondsConverter
            ?: $this->getApplication()->getContainer()->get('seconds_to_none');
    }

    protected function configure()
    {
        $this
            ->setName('internal:shell')
            ->setDescription('Shell-related features')
            ->setAliases(['shell'])
            ->setHelp('To use autocompletion in fish, add `complete -x -c jira -a "(jira shell (commandline -poc) (commandline -pot))"` to your rc file.')
            ->ignoreValidationErrors();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $args = $_SERVER['argv'];

        if ($args[0] == $_SERVER['PHP_SELF']) {
            array_shift($args); // drop script name
        }

        array_shift($args); // drop first command name as it's "shell"
        array_shift($args); // drop second command name as it's from (complete -poc) which is "jira"

        return $this->autocompletion($args, $output);
    }

    private function autocompletion($arguments, OutputInterface $output)
    {
        $command = (string) array_shift($arguments);
        if (!$this->getApplication()->has($command)) {
            $this->completeCommands($command, $output);

            return;
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
        foreach (array_merge($definition->getOptions(), $this->getApplication()
            ->getDefinition()
            ->getOptions()) as $option) {
            $isLongOpt = substr($opt, 0, 2) == '--';
            /** @var InputOption $option */
            if ($opt == '--instance' || $opt == '-i') {
                foreach ($this->getInstancesConfiguraiton()->items() as $instance) {
                    if ($instance->name() == 'default') {
                        continue;
                    }

                    $output->writeln($instance->name());
                }

                return;
            } else {
                if ($isLongOpt) {
                    $options[] = '--' . $option->getName();
                } else {
                    if (strpos($option->getShortcut(), '|') !== false) {
                        $shortcuts = explode('|', $option->getShortcut());
                    } else {
                        $shortcuts = [$option->getShortcut()];
                    }
                    foreach ($shortcuts as $shortcut) {
                        if (!empty($shortcut)) {
                            $options[] = '-' . $shortcut;
                        }
                    }
                }
            }
        }

        if (empty($options)) {
            return;
        }

        $this->searchWithFuse(new Fuse($options), $output, $options, $opt);
    }

    private function completeArgument($argOrOpt, $index, InputDefinition $definition, OutputInterface $output)
    {
        $argument = $definition->getArgument($index);
        switch ($argument->getName()) {
            case IssueKeyResolver::ARGUMENT:
            case IssueKeyOrWorklogIdResolver::NAME:
                $issues = $this->getIssueSelector()->retrieveIssuesToChooseFrom($argOrOpt);
                foreach ($issues as $issue) {
                    $output->writeln($issue->key());
                }
                $aliases = [];
                foreach ($this->getAliasesConfiguration()->items() as $item) {
                    $aliases[] = $item->alias();
                    $aliases[] = $item->issueKey();
                }
                $this->searchWithFuse(new Fuse($aliases), $output, $aliases, $argOrOpt);
                break;
            case 'jql':
                $output->writeln('"issueKey in (GEN-123)"'); //@TODO create a jql autocomplete with fuse?
                break;
            case 'time':
                $times = [15, 30, 60, 90, 120, 150, 180, 225, 240, 4 * 60, 5 * 60, 6 * 60, 7.25 * 60, 7.5 * 60];
                foreach ($times as $minutes) {
                    $human = $this->getSecondsConverter()->secondsToHuman($minutes * 60);
                    $output->writeln($human);
                }
        }
    }

    private function searchWithFuse(Fuse $fuse, OutputInterface $output, $values, $prefix)
    {
        if (!empty($prefix)) {
            $results = $fuse->search($prefix);
            $matching = array_unique(array_map(function ($index) use ($values) {
                return $values[$index];
            }, $results));
        } else {
            $matching = $values;
        }

        foreach ($matching as $match) {
            $output->writeln($match);
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
