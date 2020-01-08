<?php

namespace Fixture\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Technodelight\Jira\Console\DependencyInjection\Container\Builder;
use Technodelight\Jira\Console\DependencyInjection\Container\Provider as BaseProvider;

class Provider extends BaseProvider
{
    public function build($version)
    {
        $containerBuilder = (new Builder())->build();
        $containerBuilder->setParameter('technodelight.jira.app.version', $version);

        $loader = new XmlFileLoader(
            $containerBuilder,
            new FileLocator(sprintf('%s/features/bootstrap/configs', APPLICATION_ROOT_DIR))
        );
        $loader->load('services.xml');

        $containerBuilder->compile();

        return $containerBuilder;
    }
}
