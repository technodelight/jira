<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class ProjectConfiguration implements RegistrableConfiguration
{
    private bool $yesterdayAsWeekday;
    private string $defaultTimestamp;
    private string|int $oneDay;
    private int $cacheTtl;
    private array $config;

    public static function fromArray(array $config): ProjectConfiguration
    {
        $instance = new self;
        $instance->config = $config;
        $instance->yesterdayAsWeekday = $config['yesterdayAsWeekday'];
        $instance->defaultTimestamp = $config['defaultWorklogTimestamp'];
        $instance->oneDay = $config['oneDay'];
        $instance->cacheTtl = $config['cacheTtl'];

        return $instance;
    }

    public function yesterdayAsWeekday(): bool
    {
        return $this->yesterdayAsWeekday;
    }

    public function defaultWorklogTimestamp(): string
    {
        return $this->defaultTimestamp;
    }

    public function oneDayAmount(): string|int
    {
        return $this->oneDay;
    }

    public function cacheTtl(): int
    {
        return $this->cacheTtl;
    }

    public function servicePrefix(): string
    {
        return 'project';
    }

    public function configAsArray(): array
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
