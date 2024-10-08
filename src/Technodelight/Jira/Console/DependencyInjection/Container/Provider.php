<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\DependencyInjection\Container;

use Exception;
use ProjectServiceContainer;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Console\DependencyInjection\CacheMaintainer;

class Provider
{
    /**
     * @param string $version
     * @return Container
     * @throws Exception
     * @SuppressWarnings(PHPMD)
     */
    public function build($version)
    {
        $file = CacheMaintainer::containerCachePath();

        if (file_exists($file) && !defined('SKIP_CACHE_CONTAINER')) {
            require_once $file;
            /** @var Container $container */
            $container = new ProjectServiceContainer();

            //@TODO this stopped working with the new symfony DI version
//            $cacheMaintainer = $container->get('technodelight.jira.console.di.cache_maintainer');
//            if (!$cacheMaintainer->checkAndInvalidate()) {
//                return $container;
//            }
        }

        return $this->rebuildContainer($version);
    }

    /**
     * @param string $version
     * @return Builder
     * @throws Exception
     */
    private function rebuildContainer($version): ContainerBuilder
    {
        $containerBuilder = (new Builder())->build();
        $containerBuilder->setParameter('technodelight.jira.app.version', $version);
        $containerBuilder->compile();

        if (!defined('SKIP_CACHE_CONTAINER')) {
            $containerBuilder->get('technodelight.jira.console.di.cache_maintainer')?->dump($containerBuilder);
        }

        return $containerBuilder;
    }
}
