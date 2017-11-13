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
        $instance->name = $config['name'];
        $instance->formatter = $config['formatter'];
        $instance->inline = $config['inline'];
        $instance->before = $config['before'];
        $instance->after = $config['after'];
        $instance->remove = $config['remove'];

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
}
