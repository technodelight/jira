<?php

namespace Technodelight\Jira\Console\Application\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RendererProviderCompilerPass implements CompilerPassInterface
{
    /**
     * @throws \Exception
     */
    public function process(ContainerBuilder $builder)
    {
        $renderers = [];
        $rendererTags = $builder->findTaggedServiceIds('issue_renderer');
        foreach ($rendererTags as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['type'])) {
                    continue;
                }

                $name = $tag['type'];
                $renderers[$name] = $builder->get($serviceId);
            }
        }

        $builder
            ->getDefinition('technodelight.jira.renderer.issue.renderer_provider')
            ->setArguments([$renderers]);
    }
}
