<?php

namespace Technodelight\Jira\Console\Application;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\Registrator;
use Technodelight\Jira\Configuration\Symfony\ApplicationConfigurationCompilerPass;
use Technodelight\Jira\Configuration\Symfony\RendererProviderCompilerPass;
use Technodelight\Jira\Configuration\Symfony\SyntheticServicesCompilerPass;
use Technodelight\Jira\Console\Application;

class Bootstrap
{
    public function boot($version, array $containerPaths = [])
    {
        $app = new Application('JIRA CLI', $version);
        $app->setContainer($this->buildContainer($app, $containerPaths));
        $app->addDomainCommands();
        return $app;
    }

    private function buildContainer(Application $app, array $containerPaths)
    {
        $container = new ContainerBuilder();
        // add compiler passes
        $container->addCompilerPass(new SyntheticServicesCompilerPass($app));
        $container->addCompilerPass(new ApplicationConfigurationCompilerPass);
        $container->addCompilerPass(new RendererProviderCompilerPass);

        $loader = new XmlFileLoader($container, new FileLocator);
        foreach ($containerPaths as $containerPath) {
            foreach (glob(sprintf('%s/*.xml', $containerPath)) as $file) {;
                $loader->load($file);
            }
        }

        return $container;
    }
}
