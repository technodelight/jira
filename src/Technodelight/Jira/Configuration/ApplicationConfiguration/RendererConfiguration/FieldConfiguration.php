<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration;

class FieldConfiguration
{
    private string $name;
    private ?string $formatter;
    private ?bool $inline;
    private ?string $before;
    private ?string $after;
    private ?bool $remove;

    public static function fromArray(array $config): self
    {
        $instance = new self;
        $instance->name = $instance->withDefaults($config, 'name');
        $instance->formatter = $instance->withDefaults($config, 'formatter');
        $instance->inline = $instance->withDefaults($config, 'inline');
        $instance->before = $instance->withDefaults($config, 'before');
        $instance->after = $instance->withDefaults($config, 'after');
        $instance->remove = $instance->withDefaults($config, 'remove');

        return $instance;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function formatter(): ?string
    {
        return $this->formatter;
    }

    public function inline(): bool
    {
        return $this->inline;
    }

    public function before(): ?string
    {
        return $this->before;
    }

    public function after(): ?string
    {
        return $this->after;
    }

    public function shouldBeMoved(): bool
    {
        return !empty($this->before) || !empty($this->after);
    }

    public function remove(): ?bool
    {
        return $this->remove;
    }

    private function __construct()
    {
    }

    private function withDefaults(array $config, $key)
    {
        return $config[$key] ?? null;
    }
}
