<?php

namespace Technodelight\Jira\Console\Application;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Technodelight\Jira\Console\Application\DependencyInjection\ApplicationConfigurationCompilerPass;
use Technodelight\Jira\Console\Application\DependencyInjection\CommandInitialisationCompilerPass;
use Technodelight\Jira\Console\Application\DependencyInjection\IssueRendererOptionsCompilerPass;
use Technodelight\Jira\Console\Application\DependencyInjection\RendererProviderCompilerPass;
use Technodelight\Jira\Console\Application\DependencyInjection\SyntheticServicesCompilerPass;
use Technodelight\Jira\Console\Application;

class Bootstrap
{
    /**
     * @param string $version
     * @param array $containerPaths array of directory paths
     * @param InputInterface $input
     * @return Application
     * @throws \Exception
     */
    public function boot($version, array $containerPaths = [], InputInterface $input)
    {
        $app = new Application('JIRA CLI', $version);
        $container = $this->buildContainer($app, $containerPaths, $input);
        $app->setContainer($container);
        return $app;
    }

    /**
     * @param Application $app
     * @param array $containerPaths
     * @param InputInterface $input
     * @return ContainerBuilder
     * @throws \Exception
     */
    private function buildContainer(Application $app, array $containerPaths, InputInterface $input)
    {
        $container = new ContainerBuilder();
        $this->setContainerParamsFromInput($container, $input);

        // add compiler passes
        $container->addCompilerPass(new SyntheticServicesCompilerPass($app));
        $container->addCompilerPass(new ApplicationConfigurationCompilerPass);
        $container->addCompilerPass(new RendererProviderCompilerPass);
        $container->addCompilerPass(new CommandInitialisationCompilerPass);
        $container->addCompilerPass(new IssueRendererOptionsCompilerPass);

        $loader = new XmlFileLoader($container, new FileLocator);
        foreach ($this->containerPathsWithBaseDir($app, $containerPaths) as $containerPath) {
            $loader->load($containerPath . DIRECTORY_SEPARATOR . 'services.xml');
        }

        return $container;
    }

    /**
     * @param Application $app
     * @param array $containerPaths
     * @return array
     */
    private function containerPathsWithBaseDir(Application $app, array $containerPaths)
    {
        return array_map(function($path) use($app) {
            return join(DIRECTORY_SEPARATOR, [$app->baseDir(), $path]);
        }, $containerPaths);
    }

    private function setContainerParamsFromInput(ContainerBuilder $container, InputInterface $input)
    {
        $container->setParameter(
            'app.jira.debug',
            $input->getParameterOption(['--debug', '-d'])
        );
        $container->setParameter(
            'app.jira.instance',
            $input->getParameterOption(['--instance', '-i']) ?: 'default'
        );
    }
}
