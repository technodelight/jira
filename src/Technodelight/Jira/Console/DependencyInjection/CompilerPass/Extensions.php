<?php

namespace Technodelight\Jira\Console\DependencyInjection\CompilerPass;

use Exception;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Console\Configuration\Provider;
use Technodelight\Jira\Extension\ConfigurationPreProcessor;
use Technodelight\Jira\Extension\Locator as ExtensionLocator;

class Extensions implements CompilerPassInterface
{
    const PHP_REQUIRE_STATEMENT = 'if (is_file(\'%1$s\')) { require_once "%1$s"; } else echo(\'cannot load extension: %2$s\' . PHP_EOL);';

    /**
     * @param ContainerBuilder $container
     * @throws Exception
     */
    public function process(ContainerBuilder $container)
    {
        $this->updateDef($container);
        $this->processConfig($container);
        $this->loadExtensions($container);
    }

    /**
     * @param ContainerBuilder $container
     * @throws Exception
     */
    private function updateDef(ContainerBuilder $container)
    {
        $provider = $container->get('technodelight.jira.console.configuration.provider');
        $extensionLocator = new ExtensionLocator;

        $preProcessedConfig = (new ConfigurationPreProcessor)->preProcess($provider->get());
        $extensionClassMap = $extensionLocator->locate(
            isset($preProcessedConfig['extensions']) ? $preProcessedConfig['extensions'] : []
        );

        $def = $container->getDefinition('technodelight.jira.extension.configurator');
        $def->setArguments([
            $def->getArgument(0),
            $extensionClassMap
        ]);

        // cache extension paths for later use
        $this->cacheExtensionPaths($extensionClassMap);
    }

    private function cacheExtensionPaths(array $extensionClassMap)
    {
        $f = fopen(getenv('HOME') . '/.jira/extensions.php', 'w');
        fwrite($f, '<?php' . PHP_EOL);
        fwrite($f, '# autogenerated file; DO NOT MODIFY MANUALLY' . PHP_EOL);
        foreach ($extensionClassMap as $class => $path) {
            fwrite($f, sprintf(self::PHP_REQUIRE_STATEMENT . PHP_EOL, $path, $class));
        }
        fclose($f);
    }

    /**
     * @param ContainerBuilder $container
     * @throws Exception
     */
    private function processConfig(ContainerBuilder $container)
    {
        $container->get('technodelight.jira.extension.configurator')->configure(
            $container->get('technodelight.jira.console.configuration.configuration')->getRootNode()
        );
    }

    /**
     * @param ContainerBuilder $container
     * @throws Exception
     */
    private function loadExtensions(ContainerBuilder $container)
    {
        /** @var Provider $provider */
        $provider = $container->get('technodelight.jira.console.configuration.provider');
        $configuration = $container->get('technodelight.jira.console.configuration.configuration');

        $config = (new Processor)->process(
            $configuration->getConfigTreeBuilder()->buildTree(), $provider->get()
        );

        $container->get('technodelight.jira.extension.configurator')->load(
            $config,
            $container
        );
    }
}
