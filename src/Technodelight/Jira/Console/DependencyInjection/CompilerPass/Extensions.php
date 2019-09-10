<?php

namespace Technodelight\Jira\Console\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Extension\ConfigProcessor;
use Technodelight\Jira\Extension\Loader as ExtensionLoader;

class Extensions implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $this->updateDef($container);
        $this->processConfig($container);
        $this->loadExtensions($container);
    }

    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    private function updateDef(ContainerBuilder $container): void
    {
        $provider = $container->get('technodelight.jira.console.configuration.provider');
        $extensionLoader = new ExtensionLoader;
        $extensionConfigProcessor = new ConfigProcessor;

        $extensions = $extensionLoader->loadExtensions($extensionConfigProcessor->process($provider->get()));

        $def = $container->getDefinition('technodelight.jira.extension.configurator');
        $def->setArguments([$extensions]);
    }

    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    private function processConfig(ContainerBuilder $container)
    {
        $container->get('technodelight.jira.extension.configurator')->configure(
            $container->get('technodelight.jira.console.configuration.configuration')->getConfigTreeBuilder()
        );
    }

    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    private function loadExtensions(ContainerBuilder $container)
    {
        $container->get('technodelight.jira.extension.configurator')->load(
            $container->get('technodelight.jira.console.configuration.provider')->get(),
            $container
        );
    }
}
