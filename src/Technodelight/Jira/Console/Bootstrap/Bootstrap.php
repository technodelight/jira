<?php

namespace Technodelight\Jira\Console\Bootstrap;

use Technodelight\Jira\Console\Application;
use Technodelight\Jira\Console\DependencyInjection\Container\Provider;

class Bootstrap
{
    private Provider $containerProvider;

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
        return $this->containerProvider->build($version)->get('technodelight.jira.app');
    }
}
