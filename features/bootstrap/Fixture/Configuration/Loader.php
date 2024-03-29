<?php

namespace Fixture\Configuration;

use Symfony\Component\Yaml\Yaml;
use Technodelight\Jira\Console\Configuration\Loader as BaseLoader;

class Loader extends BaseLoader
{
    public static array $configs = [];

    public function load(array $directories): array
    {
        $configurations = array_merge(
            Yaml::parse(file_get_contents(APPLICATION_ROOT_DIR . '/features/bootstrap/fixtures/configuration.yml')),
            self::$configs
        );

        return [$configurations];
    }
}
