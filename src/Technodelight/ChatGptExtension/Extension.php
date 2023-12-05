<?php

namespace {
    spl_autoload_register(function ($className) {
        if (strpos($className, 'Technodelight\\ChatGptExtension') === false) {
            return;
        }
        $path = str_replace('\\', DIRECTORY_SEPARATOR, str_replace('Technodelight\\ChatGptExtension\\', '', $className));
        if (is_file('./' . $path . '.php')) {
            require_once './' . $path . '.php';
        }
    });
}

namespace Technodelight\ChatGptExtension
{
    use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
    use Symfony\Component\Config\Definition\Builder\TreeBuilder;
    use Symfony\Component\Config\FileLocator;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
    use Technodelight\Jira\Extension\ExtensionInterface;

    class Extension implements ExtensionInterface
    {
        public function load(array $configs, ContainerBuilder $container): void
        {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Resources'));
            $loader->load('services.xml');
        }

        public function configure(): ArrayNodeDefinition
        {
            $builder = new TreeBuilder('chatgpt');
            $node = $builder->getRootNode();
            $node->info('ChatGPT API credentials');

            $node->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('apiKey')->attribute('hidden', true)->defaultNull()->end()
                    ->scalarNode('organization')->defaultNull()->end()
                ->end()
            ->end();

            return $node;
        }
    }
}
