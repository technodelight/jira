<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class FiltersConfiguration implements RegistrableConfiguration
{
    /**
     * @var FilterConfiguration
     */
    private $filters;

    public static function fromArray(array $config)
    {
        $instance = new self;

        $instance->filters = array_map(
            function (array $filter) {
                return FilterConfiguration::fromArray($filter);
            },
            $config
        );

        return $instance;
    }

    public function items()
    {
        return $this->filters;
    }

    public function servicePrefix()
    {
        return 'filters';
    }

    private function __construct()
    {
    }
}
