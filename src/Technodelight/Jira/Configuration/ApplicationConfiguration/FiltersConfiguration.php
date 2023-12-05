<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class FiltersConfiguration implements RegistrableConfiguration
{
    /**
     * @var FilterConfiguration[]
     */
    private $filters;
    /**
     * @var array
     */
    private $config;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->config = $config;

        $instance->filters = array_map(
            function (array $filter) {
                return FilterConfiguration::fromArray($filter);
            },
            $config
        );

        return $instance;
    }

    /**
     * @return FilterConfiguration[]
     */
    public function items()
    {
        return $this->filters;
    }

    public function servicePrefix(): string
    {
        return 'filters';
    }

    /**
     * @return array
     */
    public function configAsArray(): array
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
