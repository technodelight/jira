<?php

namespace {
    spl_autoload_register(function ($className) {
        if (!str_starts_with($className, 'Technodelight\\ChatGptExtension')) {
            return;
        }
        $path = str_replace(
            '\\',
            DIRECTORY_SEPARATOR,
            str_replace('Technodelight\\ChatGptExtension\\', '', $className)
        );

        if (is_file(__DIR__ . DIRECTORY_SEPARATOR . $path . '.php')) {
            require_once __DIR__ . DIRECTORY_SEPARATOR . $path . '.php';
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
    use Symfony\Component\DependencyInjection\Reference;
    use Technodelight\ChatGptExtension\Api\Api;
    use Technodelight\Jira\Extension\ExtensionInterface;

    class Extension implements ExtensionInterface
    {
        public function load(array $configs, ContainerBuilder $container): void
        {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Resources'));
            $loader->load('services.xml');

            $def = $container->getDefinition('technodelight.jira.checkout_branch');
            $def->setArgument(2, new Reference('technodelight.chatgpt.git_branchname_generator'));
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
                    ->scalarNode('model')->defaultValue(Api::MODEL)->end()
                ->end()
            ->end();

            return $node;
        }
    }
}
