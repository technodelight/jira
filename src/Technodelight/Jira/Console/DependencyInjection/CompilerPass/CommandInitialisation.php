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
            } else {
                $this->createAndAddServiceCommand(
                    'filter',
                    $filter->command(),
                    $container,
                    $this->createFilterDef($container, [$filter->command(), $filter->jql()])
                );
            }
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

    private function createTransitionDef(ContainerBuilder $container, TransitionConfiguration $transition): Definition
    {
        return new Definition(
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
                $container->getDefinition('technodelight.jira.renderer.action.issue.transition'),
                $container->getDefinition('technodelight.jira.console.argument.assignee_autocomplete'),
                $container->getDefinition('technodelight.jira.console.argument.issue_key_autocomplete')
            ]
        );
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
