<?php

namespace Technodelight\Jira\Console\Application\Bootstrap;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Technodelight\Jira\Console\Application\DependencyInjection\ApplicationConfigurationCompilerPass;
use Technodelight\Jira\Console\Application\DependencyInjection\CacheMaintainer;
use Technodelight\Jira\Console\Application\DependencyInjection\CommandInitialisationCompilerPass;
use Technodelight\Jira\Console\Application\DependencyInjection\CommandRegistrationCompilerPass;
use Technodelight\Jira\Console\Application\DependencyInjection\IssueRendererOptionsCompilerPass;
use Technodelight\Jira\Console\Application\DependencyInjection\RendererProviderCompilerPass;

class Container
{
    /**
     * @var array
     */
    private $containerPaths;
    /**
     * @var string
     */
    private $version;

    public function __construct(array $containerPaths, $version)
    {
        $this->containerPaths = $containerPaths;
        $this->version = $version;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\Container
     * @throws \Exception
     */
    public function process()
    {
        return $this->container($this->containerPaths, $this->version);
    }

    /**
     * @param array $containerPaths
     * @param string $version
     * @return \Symfony\Component\DependencyInjection\Container
     * @throws \Exception
     */
    private function container(array $containerPaths, $version)
    {
        $file = CacheMaintainer::containerCachePath();

        if (file_exists($file) && !defined('SKIP_CACHE_CONTAINER')) {
            require_once $file;
            /** @var \Symfony\Component\DependencyInjection\Container $container */
            $container = new \ProjectServiceContainer();

            $cacheMaintainer = $container->get('technodelight.jira.console.di.cache_maintainer');
            if (!$cacheMaintainer->checkAndInvalidate()) {
                return $container;
            }
        }

        //@TODO: because of reasons, this thing doesn't work as expected. Somehow the application does not have any commands after regenerating container
        //@TODO: check if anything builds up the application before commands are added?
        return $this->rebuildContainer($version, $containerPaths);
    }

    /**
     * @param array $containerPaths
     * @return ContainerBuilder
     * @throws \Exception
     */
    private function buildContainer(array $containerPaths)
    {
        $container = new ContainerBuilder();

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(APPLICATION_ROOT_DIR)
        );
        foreach ($containerPaths as $containerPath) {
            $loader->load($containerPath . DIRECTORY_SEPARATOR . 'services.xml');
        }

        // add compiler passes
        $container->addCompilerPass(new ApplicationConfigurationCompilerPass);
        $container->addCompilerPass(new RendererProviderCompilerPass);
        $container->addCompilerPass(new CommandInitialisationCompilerPass);
        $container->addCompilerPass(new IssueRendererOptionsCompilerPass);
        $container->addCompilerPass(new CommandRegistrationCompilerPass);

        return $container;
    }

    /**
     * @param string $version
     * @param array $containerPaths
     * @return ContainerBuilder
     * @throws \Exception
     */
    private function rebuildContainer($version, array $containerPaths)
    {
        $containerBuilder = $this->buildContainer($containerPaths);
        $containerBuilder->setParameter('technodelight.jira.app.version', $version);
        $containerBuilder->compile();

        if (!defined('SKIP_CACHE_CONTAINER')) {
            $containerBuilder->get('technodelight.jira.console.di.cache_maintainer')->dump($containerBuilder);
        }

        return $containerBuilder;
    }
}
