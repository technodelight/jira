<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\FilterConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\TransitionConfiguration;
use Technodelight\Jira\Console\Command\Filter\IssueFilter;
use Technodelight\Jira\Console\Command\Filter\StoredIssueFilter;

class CommandInitialisation implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /** @var ApplicationConfiguration $config */
        $config = $container->get('technodelight.jira.config');
        $this->prepareCommandsFromConfiguration($container, $config);
    }

    private function prepareCommandsFromConfiguration(
        ContainerBuilder $container,
        ApplicationConfiguration $config
    ): void {
        foreach ($config->transitions()->items() as $transition) {
            $this->createAndAddServiceCommand(
                'transition',
                $transition->command(),
                $container,
                $this->createTransitionDef($container, $transition)
            );
        }

        // issue listing commands
        $filters = $config->filters();
        foreach ($filters->items() as $filter) {
            if (null !== $filter->filterId()) {
                $this->createAndAddStoredFilter(
                    $container,
                    $filter
                );
                continue;
            }

            $this->createAndAddServiceCommand(
                'filter',
                $filter->command(),
                $container,
                $this->createFilterDef($container, [$filter->command(), $filter->jql()])
            );
        }
    }

    private function createAndAddServiceCommand(
        string $type,
        string $command,
        ContainerBuilder $container,
        Definition $definition
    ): void {
        $serviceId = sprintf(
            'technodelight.jira.app.command.%s.%s',
            $type,
            strtr($command, ['-' => '_', ':' => '_'])
        );
        $container->setDefinition($serviceId, $definition);
        $container->getDefinition($serviceId)->addTag('command');
    }

    private function createTransitionDef(ContainerBuilder $builder, TransitionConfiguration $transition): Definition
    {
        $def = clone $builder->getDefinition('technodelight.jira.app.command.filter.abstract');
        $def->setAbstract(false);
        $def->setArgument('$name', $transition->command());
        $def->setArgument('$transitions', $transition->transitions());
        $def->addTag('command');

        return $def;
    }

    private function createFilterDef(ContainerBuilder $container, array $arguments): Definition
    {
        $definition = new Definition(IssueFilter::class, $arguments);
        $definition->addMethodCall('setJiraApi', [$container->getDefinition('technodelight.jira.api')]);
        $definition->addMethodCall(
            'setIssueRenderer', [$container->getDefinition('technodelight.jira.issue_renderer')]
        );

        return $definition;
    }

    private function createAndAddStoredFilter(ContainerBuilder $container, FilterConfiguration $filter): Definition
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
