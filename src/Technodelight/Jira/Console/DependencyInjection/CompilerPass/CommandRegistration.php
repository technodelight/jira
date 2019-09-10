<?php

namespace Technodelight\Jira\Console\DependencyInjection\CompilerPass;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Technodelight\Jira\Console\Application;

class CommandRegistration implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $appDef = $container->getDefinition('technodelight.jira.app');
        $commandServiceIds = array_keys($container->findTaggedServiceIds('command'));
        foreach ($commandServiceIds as $serviceId) {
            $appDef->addMethodCall('add', [new Reference($serviceId)]);
        }

        /** @var Application $app */
        $app = $container->get('technodelight.jira.app');
        foreach ($commandServiceIds as $serviceId) {
            /** @var Command $command */
            $command = $container->get($serviceId);
            $app->add($command);
        }
    }
}
