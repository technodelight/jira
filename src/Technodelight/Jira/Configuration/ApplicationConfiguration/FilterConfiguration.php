<?php

declare(strict_types=1);

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

class FilterConfiguration
{
    private string $command;
    private string $jql;
    private ?int $filterId;
    private ?string $instance;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->command = $config['command'];
        $instance->jql = $config['jql'];
        $instance->filterId = !empty($config['filterId']) ? $config['filterId'] : null;
        $instance->instance = !empty($config['instance']) ? $config['instance'] : null;

        return $instance;
    }

    public function command(): string
    {
        return $this->command;
    }

    public function jql(): string
    {
        return $this->jql;
    }

    public function filterId(): ?int
    {
        return $this->filterId;
    }

    public function instance(): ?string
    {
        return $this->instance;
    }

    private function __construct()
    {
    }
}
