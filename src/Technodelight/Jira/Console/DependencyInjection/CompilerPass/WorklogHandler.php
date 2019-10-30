<?php

namespace Technodelight\Jira\Console\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WorklogHandler implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        $worklogHandlers = [];
        foreach ($container->findTaggedServiceIds('worklog_handler') as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['key'])) {
                    continue;
                }

                $worklogHandlers[$tag['key']] = new Reference($serviceId);
            }
        }

        $def = $container->getDefinition('technodelight.jira.connector.worklog_handler_factory');
        $args = $def->getArguments();
        $args[1] = $worklogHandlers;
        $def->setArguments($args);
    }
}
