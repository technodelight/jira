<?php

namespace Technodelight\Jira\Console\Application\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Console\Command\Action\Issue\Transition;
use Technodelight\Jira\Console\Command\Filter\IssueFilter;

class CommandInitialisationCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
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

    /**
     * @param ContainerBuilder $container
     * @param ApplicationConfiguration $config
     * @param array $commands
     * @throws \Exception
     */
    private function prepareCommandsFromConfiguration(ContainerBuilder $container, ApplicationConfiguration $config, array &$commands)
    {
        foreach ($config->transitions()->items() as $transition) {
            $commands[] = $this->createServiceCommand(
                'transition',
                $transition->command(),
                $container,
                new Definition(
                    Transition::class,
                    [
                        $container,
                        $transition->command(),
                        $transition->transitions()
                    ]
                )
            );
        }

        /** @var \Technodelight\Jira\Api\JiraRestApi\Api $jira */
        $jira = $container->get('technodelight.jira.api');

        // issue listing commands
        $filters = $config->filters();
        $currentInstance = $container->getParameter('app.jira.instance');
        foreach ($filters->items() as $filter) {
            if (!empty($filter->filterId()) && $filter->instance() == $currentInstance) {
                $arguments = [$container, $filter->command(), $filter->jql() . $jira->retrieveFilter($filter->filterId())->jql()];
            } else {
                $arguments = [$container, $filter->command(), $filter->jql()];
            }

            $definition = new Definition(IssueFilter::class, $arguments);
            $commands[] = $this->createServiceCommand('filter', $filter->command(), $container, $definition);
        }
    }

    /**
     * @param string $type
     * @param string $command
     * @param ContainerBuilder $container
     * @param Definition $definition
     * @return object
     * @throws \Exception
     */
    private function createServiceCommand($type, $command, ContainerBuilder $container, Definition $definition)
    {
        $serviceId = sprintf('technodelight.jira.app.command.%s.%s', $type, $command);
        $container->setDefinition($serviceId, $definition);
        $container->getDefinition($serviceId)->addTag('command');
        return $container->get($serviceId);
    }
}
