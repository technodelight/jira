<?php

declare(strict_types=1);

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

/** @SuppressWarnings(PHPMD.StaticAccess) */
class FiltersConfiguration implements RegistrableConfiguration
{
    /** @var FilterConfiguration[] */
    private array $filters;
    private array $config;

    public static function fromArray(array $config): FiltersConfiguration
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

    public function items(): array
    {
        return $this->filters;
    }

    public function servicePrefix(): string
    {
        return 'filters';
    }

    public function configAsArray(): array
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
