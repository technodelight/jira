<?php

namespace Technodelight\Jira\Console\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RendererProvider implements CompilerPassInterface
{
    /**
     * @throws \Exception
     */
    public function process(ContainerBuilder $builder)
    {
        $renderersByType = [];
        $rendererTags = $builder->findTaggedServiceIds('issue_renderer');
        foreach ($rendererTags as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['key'])) {
                    continue;
                }
                $tag['types'] = array_map('trim', explode(',', $tag['types'] ?? 'standard'));

                $key = $tag['key'];
                foreach ($tag['types'] as $type) {
                    $renderersByType[$type][$key] = $builder->getDefinition($serviceId);
                }
            }
        }

        foreach ($renderersByType as $type => $renderers) {
            $builder
                ->getDefinition(sprintf('technodelight.jira.renderer.issue.%s.renderer_provider', $type))
                ->setArguments([$renderers]);
        }
    }
}
