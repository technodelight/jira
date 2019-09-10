<?php

namespace Technodelight\Jira\Console\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\FilterConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\TransitionConfiguration;
use Technodelight\Jira\Console\Command\Action\Issue\Transition;
use Technodelight\Jira\Console\Command\Filter\IssueFilter;
use Technodelight\Jira\Console\Command\Filter\StoredIssueFilter;

class CommandInitialisation implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        /** @var ApplicationConfiguration $config */
        $config = $container->get('technodelight.jira.config');
        $this->prepareCommandsFromConfiguration($container, $config);
    }

    /**
     * @param ContainerBuilder $container
     * @param ApplicationConfiguration $config
     * @return Definition[]
     * @throws \Exception
     */
    private function prepareCommandsFromConfiguration(ContainerBuilder $container, ApplicationConfiguration $config)
    {
        $commands = [];
        foreach ($config->transitions()->items() as $transition) {
            $commands[] = $this->createServiceCommand(
                'transition',
                $transition->command(),
                $container,
                $this->createTransitionDef($container, $transition)
            );
        }

        // issue listing commands
        $filters = $config->filters();
        foreach ($filters->items() as $filter) {
            if (!empty($filter->filterId())) {
                $commands[] = $this->createStoredFilterDef(
                    $container,
                    $filter
                );
            } else {
                $commands[] = $this->createServiceCommand(
                    'filter',
                    $filter->command(),
                    $container,
                    $this->createFilterDef($container, [$filter->command(), $filter->jql()])
                );
            }
        }

        return $commands;
    }

    /**
     * @param string $type
     * @param string $command
     * @param ContainerBuilder $container
     * @param Definition $definition
     * @return Definition
     * @throws \Exception
     */
    private function createServiceCommand($type, $command, ContainerBuilder $container, Definition $definition)
    {
        $serviceId = sprintf(
            'technodelight.jira.app.command.%s.%s',
            $type,
            strtr($command, ['-' => '_'])
        );
        $container->setDefinition($serviceId, $definition);
        $container->getDefinition($serviceId)->addTag('command');
        return $container->getDefinition($serviceId);
    }

    /**
     * @param ContainerBuilder $container
     * @param TransitionConfiguration $transition
     * @return Definition
     */
    private function createTransitionDef(ContainerBuilder $container, TransitionConfiguration $transition)
    {
        $definition = new Definition(
            Transition::class,
            [
                $transition->command(),
                $transition->transitions(),
                $container->getDefinition('technodelight.jira.api'),
                $container->getDefinition('technodelight.jira.console.argument.issue_key_resolver'),
                $container->getDefinition('technodelight.jira.checkout_branch'),
                $container->getDefinition('technodelight.gitshell.api'),
                $container->getDefinition('technodelight.jira.template_helper'),
                $container->getDefinition('technodelight.jira.console.option.checker'),
                $container->getDefinition('technodelight.jira.console.input.issue.assignee'),
                $container->getDefinition('console.question_helper'),
                $container->getDefinition('technodelight.jira.renderer.action.issue.transition')
            ]
        );

        return $definition;
    }

    /**
     * @param ContainerBuilder $container
     * @param array $arguments
     * @return Definition
     */
    private function createFilterDef(ContainerBuilder $container, array $arguments)
    {
        $definition = new Definition(IssueFilter::class, $arguments);
        $definition->addMethodCall('setJiraApi', [$container->getDefinition('technodelight.jira.api')]);
        $definition->addMethodCall('setIssueRenderer', [$container->getDefinition('technodelight.jira.issue_renderer')]);

        return $definition;
    }

    /**
     * @param ContainerBuilder $container
     * @param FilterConfiguration $filter
     * @return Definition
     */
    private function createStoredFilterDef(ContainerBuilder $container, FilterConfiguration $filter)
    {
        $filterDef = new Definition(FilterConfiguration::class);
        $filterDef->setFactory([FilterConfiguration::class, 'fromArray']);
        $filterDef->setArguments([
            [
                'command' => $filter->command(),
                'jql' => $filter->jql(),
                'filterId' => $filter->filterId(),
                'instance' => $filter->instance()
            ]
        ]);

        return new Definition(StoredIssueFilter::class, [
            $container->getDefinition('technodelight.jira.api'),
            $container->getDefinition('technodelight.jira.issue_renderer'),
            $filterDef
        ]);
    }
}
