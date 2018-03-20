<?php

namespace Technodelight\Jira\Console\Application;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Technodelight\Jira\Configuration\Symfony\ApplicationConfigurationCompilerPass;
use Technodelight\Jira\Configuration\Symfony\CommandInitialisationCompilerPass;
use Technodelight\Jira\Configuration\Symfony\RendererProviderCompilerPass;
use Technodelight\Jira\Configuration\Symfony\SyntheticServicesCompilerPass;
use Technodelight\Jira\Console\Application;

class Bootstrap
{

    public function boot($version, array $containerPaths = [])
    {
        $app = new Application('JIRA CLI', $version);
        $container = $this->buildContainer($app, $containerPaths);
        $app->setContainer($container);
        return $app;
    }

    private function buildContainer(Application $app, array $containerPaths)
    {
        $container = new ContainerBuilder();

        // add compiler passes
        $container->addCompilerPass(new SyntheticServicesCompilerPass($app));
        $container->addCompilerPass(new ApplicationConfigurationCompilerPass);
        $container->addCompilerPass(new RendererProviderCompilerPass);
        $container->addCompilerPass(new CommandInitialisationCompilerPass);

        $loader = new XmlFileLoader($container, new FileLocator);
        foreach ($this->containerPathsWithBaseDir($app, $containerPaths) as $containerPath) {
            foreach (scandir($containerPath) as $file) {
                if ($this->isXml($file)) {
                    $loader->load($containerPath . DIRECTORY_SEPARATOR . $file);
                }
            }
        }

        //@TODO: how to cache container properly?
        return $container;
    }

    /**
     * @param \Technodelight\Jira\Console\Application $app
     * @param array $containerPaths
     * @return array
     */
    private function containerPathsWithBaseDir(Application $app, array $containerPaths)
    {
        return array_map(function($path) use($app) {
            return join(DIRECTORY_SEPARATOR, [$app->baseDir(), $path]);
        }, $containerPaths);
    }

    /**
     * @param string $file
     * @return bool
     */
    private function isXml($file)
    {
        return pathinfo($file, PATHINFO_EXTENSION) == 'xml';
    }
}
