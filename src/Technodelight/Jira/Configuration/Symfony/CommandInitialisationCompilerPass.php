<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Console\Command\Action\Issue\Transition;
use Technodelight\Jira\Console\Command\Filter\IssueFilter;

class CommandInitialisationCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $commands = [];
        $commandServiceIds = array_keys($container->findTaggedServiceIds('command'));

        foreach ($commandServiceIds as $serviceId) {
            $commands[$serviceId] = $container->get($serviceId);
        }

        /** @var ApplicationConfiguration $config */
        $config = $container->get('technodelight.jira.config');
        $this->prepareCommandsFromConfiguration($container, $config, $commands);

        /** @var \Technodelight\Jira\Console\Application $app */
        $app = $container->get('technodelight.jira.app');
        $app->addCommands($commands);
    }

    private function prepareCommandsFromConfiguration(ContainerBuilder $container, ApplicationConfiguration $config, array &$commands)
    {
        foreach ($config->transitions()->items() as $transition) {
            $commands[] = new Transition(
                $container,
                $transition->command(),
                $transition->transitions()
            );
        }

        // issue listing commands
        $filters = $config->filters();
        foreach ($filters->items() as $filter) {
            $commands[] = new IssueFilter(
                $container,
                $filter->command(),
                $filter->jql()
            );
        }
    }
}
