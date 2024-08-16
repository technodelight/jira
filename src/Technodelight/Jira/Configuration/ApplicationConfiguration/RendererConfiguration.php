<?php

declare(strict_types=1);

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration\FieldConfiguration;

class RendererConfiguration
{
    private string $name;
    private bool $inherit;
    private array $fields;

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    public static function fromArray(array $config): RendererConfiguration
    {
        $instance = new self;
        $instance->name = $config['name'];
        $instance->inherit = $config['inherit'] ?? true;
        $instance->fields = array_map(
            function(array $field) {
                return FieldConfiguration::fromArray($field);
            },
            $config['fields'] ?? []
        );

        return $instance;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function inherit(): bool
    {
        return $this->inherit;
    }

    /** @return FieldConfiguration[] */
    public function fields(): array
    {
        return $this->fields;
    }

    private function __construct()
    {
    }
}
