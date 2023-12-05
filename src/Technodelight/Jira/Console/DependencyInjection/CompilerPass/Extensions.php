<?php

namespace Technodelight\Jira\Console\DependencyInjection\CompilerPass;

use Exception;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Console\Configuration\Provider;
use Technodelight\Jira\Extension\ConfigurationPreProcessor;
use Technodelight\Jira\Extension\Locator as ExtensionLocator;

class Extensions implements CompilerPassInterface
{
    private const PHP_REQUIRE_STATEMENT =
        'if (is_file(\'%1$s\')) { require_once "%1$s"; } else echo(\'cannot load extension: %2$s\' . PHP_EOL);';

    /** @throws Exception */
    public function process(ContainerBuilder $container): void
    {
        $this->updateDef($container);
        $this->processConfig($container);
        $this->loadExtensions($container);
    }

    /** @throws Exception */
    private function updateDef(ContainerBuilder $container): void
    {
        $provider = $container->get('technodelight.jira.console.configuration.provider');
        $extensionLocator = new ExtensionLocator;

        $preProcessedConfig = (new ConfigurationPreProcessor)->preProcess($provider->get());
        $extensionClassMap = $extensionLocator->locate(
            $preProcessedConfig['extensions'] ?? []
        );

        $def = $container->getDefinition('technodelight.jira.extension.configurator');
        $def->setArguments([
            $def->getArgument(0),
            $extensionClassMap
        ]);

        // cache extension paths for later use
        $this->cacheExtensionPaths($extensionClassMap);
    }

    private function cacheExtensionPaths(array $extensionClassMap): void
    {
        $filename = getenv('HOME') . '/.jira/extensions.php';
        if (!is_dir(dirname($filename))) {
            @mkdir(dirname($filename), 0775);
        }
        $f = fopen($filename, 'w');
        fwrite($f, '<?php' . PHP_EOL);
        fwrite($f, '# autogenerated file; DO NOT MODIFY MANUALLY' . PHP_EOL);
        foreach ($extensionClassMap as $class => $path) {
            fwrite($f, sprintf(self::PHP_REQUIRE_STATEMENT . PHP_EOL, $path, $class));
        }
        fclose($f);
    }

    /** @throws Exception */
    private function processConfig(ContainerBuilder $container): void
    {
        $container->get('technodelight.jira.extension.configurator')->configure(
            $container->get('technodelight.jira.console.configuration.configuration')->getRootNode()
        );
    }

    /** @throws Exception */
    private function loadExtensions(ContainerBuilder $container): void
    {
        /** @var Provider $provider */
        $provider = $container->get('technodelight.jira.console.configuration.provider');
        /** @var Configuration $configuration */
        $configuration = $container->get('technodelight.jira.console.configuration.configuration');

        $config = (new Processor)->process(
            $configuration->getConfigTreeBuilder()->buildTree(), $provider->get()
        );

        $container->get('technodelight.jira.extension.configurator')?->load(
            $config,
            $container
        );
    }
}
