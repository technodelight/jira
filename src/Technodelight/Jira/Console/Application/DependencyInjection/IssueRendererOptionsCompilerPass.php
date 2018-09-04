<?php

namespace Technodelight\Jira\Console\Application\DependencyInjection;

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
        /** @var RenderersConfiguration $renderersConfiguration */
        $renderersConfiguration = $container->get('technodelight.jira.config.renderers');
        $this->addContainerDefinitionsForRenderers($renderersConfiguration, $container);

        $commandServiceIds = array_keys($container->findTaggedServiceIds('command'));
        foreach ($commandServiceIds as $serviceId) {
            $commandDef = $container->getDefinition($serviceId);

            if ($container->get($serviceId) instanceof IssueRendererAware) {
                foreach ($renderersConfiguration->modes() as $configuration) {
                    $commandDef->addMethodCall(
                        'addOption',
                        [
                            $configuration->name(),
                            null,
                            InputOption::VALUE_NONE,
                            sprintf('Render issues using %s mode', $configuration->name())
                        ]
                    );
                    $container->get($serviceId)->addOption(
                        $configuration->name(),
                        null,
                        InputOption::VALUE_NONE,
                        sprintf('Render issues using %s mode', $configuration->name())
                    );
                }
                // special, board renderer
                $commandDef->addMethodCall(
                    'addOption',
                    [
                        'board',
                        null,
                        InputOption::VALUE_NONE,
                        sprintf('Render in board view')
                    ]
                );
                $container->get($serviceId)->addOption(
                    'board',
                    null,
                    InputOption::VALUE_NONE,
                    sprintf('Render in board view')
                );
            }
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
}
