<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

class FormatterConfiguration
{
    private $name;
    private $class;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->name = $config['name'];
        $instance->class = $config['class'];
        return $instance;
    }

    public function name()
    {
        return $this->name;
    }

    public function className()
    {
        return $this->class;
    }

    public function createInstance()
    {
        return new $this->class;
    }
}
