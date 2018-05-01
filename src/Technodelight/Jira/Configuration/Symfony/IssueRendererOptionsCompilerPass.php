<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RenderersConfiguration;
use Technodelight\Jira\Console\Command\IssueRendererAware;
use Technodelight\Jira\Renderer\IssueRenderer;

class IssueRendererOptionsCompilerPass implements CompilerPassInterface
{
    /**
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        /** @var Command[] $commands */
        $commands = [];
        $commandServiceIds = array_keys($container->findTaggedServiceIds('command'));

        foreach ($commandServiceIds as $serviceId) {
            $command = $container->get($serviceId);
            if ($command instanceof IssueRendererAware) {
                $commands[$serviceId] = $container->get($serviceId);
            }
        }

        /** @var RenderersConfiguration $renderersConfiguration */
        $renderersConfiguration = $container->get('technodelight.jira.config.renderers');
        $this->addContainerDefinitionsForRenderers($renderersConfiguration, $container);

        // add command options by renderers
        foreach ($commands as $command) {
            $this->addCommandOptions($renderersConfiguration, $command);
        }
    }

    private function addCommandOptions(RenderersConfiguration $config, Command $command)
    {
        foreach ($config->modes() as $modeName => $rendererConfiguration) {
            $optionName = $this->optionNameFromRendererMode($command, $modeName);
            $command->addOption(
                $optionName,
                null,
                InputOption::VALUE_NONE,
                sprintf('Render issues with %s mode', $modeName)
            );
        }
    }

    private function addContainerDefinitionsForRenderers(RenderersConfiguration $renderersConfiguration, ContainerBuilder $container)
    {
        $coreRendererDefinition = $container->getDefinition('technodelight.jira.issue_renderer');
        $rendererCollectionArgument = $coreRendererDefinition->getArgument(0);

        foreach ($renderersConfiguration->modes() as $modeName => $configuration) {
            $serviceId = sprintf('technodelight.jira.renderer.issue.%s', $modeName);
            $container->register($serviceId, IssueRenderer::class);
            $container->getDefinition($serviceId)
                ->setFactory([new Reference('technodelight.jira.renderer.issue.factory'), 'build'])
                ->addArgument($modeName);
            $rendererCollectionArgument[$modeName] = new Reference($serviceId);
        }

        $coreRendererDefinition->replaceArgument(0, $rendererCollectionArgument);
    }

    /**
     * @param \Symfony\Component\Console\Command\Command $command
     * @param string $modeName
     * @return string
     * @throws \InvalidArgumentException
     */
    private function optionNameFromRendererMode(Command $command, $modeName)
    {
        $optionName = $modeName;
        if ($command->getDefinition()->hasOption($optionName)) {
            $optionName = sprintf('render-%s', $modeName);
        }
        if ($command->getDefinition()->hasOption($optionName)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot use %s as renderer option, as the command already has it explicitly defined. Please choose another name for this renderer.', $modeName)
            );
        }
        return $optionName;
    }
}
