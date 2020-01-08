<?php

namespace {
    spl_autoload_register(function ($className) {
        if (strpos($className, 'Technodelight\\SkeletonExtension') === false) {
            return;
        }
        $path = str_replace('\\', DIRECTORY_SEPARATOR, str_replace('Technodelight\\SkeletonExtension\\', '', $className));
        if (is_file('./' . $path . '.php')) {
            require_once './' . $path . '.php';
        }
    });
}

namespace Technodelight\SkeletonExtension
{
    use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
    use Symfony\Component\Config\FileLocator;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
    use Technodelight\Jira\Extension\ExtensionInterface;

    class Extension implements ExtensionInterface
    {
        public function load(array $configs, ContainerBuilder $container)
        {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Resources'));
            $loader->load('services.xml');
        }

        public function configure(): ArrayNodeDefinition
        {
            // TODO: Implement configure() method.
        }
    }
}
