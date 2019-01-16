<?php

namespace Technodelight\Jira\Console\Application;

use Technodelight\Jira\Console\Application;
use Technodelight\Jira\Console\Application\Bootstrap\Container;

class Bootstrap
{
    /**
     * @param string $version
     * @param array $containerPaths array of directory paths
     * @return Application
     * @throws \Exception
     */
    public function boot($version, array $containerPaths = [])
    {
        $containerBoot = new Container($containerPaths, $version);
        $container = $containerBoot->process();

        return $container->get('technodelight.jira.app');
    }
}
