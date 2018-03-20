<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\RendererConfiguration;

class FieldConfiguration
{
    private $name;
    private $formatter;
    private $inline;
    private $before;
    private $after;
    private $remove;

    public static function fromArray(array $config)
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

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function formatter()
    {
        return $this->formatter;
    }

    /**
     * @return bool
     */
    public function inline()
    {
        return $this->inline;
    }

    /**
     * @return string|null
     */
    public function before()
    {
        return $this->before;
    }

    /**
     * @return string|null
     */
    public function after()
    {
        return $this->after;
    }

    /**
     * @return bool
     */
    public function shouldBeMoved()
    {
        return $this->before || $this->after;
    }

    /**
     * @return bool|null
     */
    public function remove()
    {
        return $this->remove;
    }

    private function __construct()
    {
    }

    private function withDefaults(array $config, $key, $default = '')
    {
        return isset($config[$key]) ? $config[$key] : $default;
    }
}
