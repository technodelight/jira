<?php

namespace Technodelight\Jira\Console\Bootstrap;

use Technodelight\Jira\Console\Application;
use Technodelight\Jira\Console\DependencyInjection\Container\Provider;

class Bootstrap
{
    /**
     * @var Provider
     */
    private $containerProvider;

    public function __construct(Provider $provider = null)
    {
        $this->containerProvider = $provider ?: new Provider;
    }

    /**
     * @param string $version
     * @return Application
     * @throws \Exception
     */
    public function boot($version): Application
    {
        $container = $this->containerProvider->build($version);

        return $container->get('technodelight.jira.app');
    }
}
