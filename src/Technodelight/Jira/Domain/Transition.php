<?php

namespace Technodelight\Jira\Domain;

class Transition
{
    private $id;
    private $name;
    private $resolvesToId;
    private $resolvesToName;
    private $resolvesToDescription;

    public static function fromArray(array $transition)
    {
        $instance = new self;
        $instance->id = $transition['id'];
        $instance->name = $transition['name'];
        $instance->resolvesToId = $transition['to']['id'];
        $instance->resolvesToName = $transition['to']['name'];
        $instance->resolvesToDescription = $transition['to']['description'];

        return $instance;
    }

    /**
     * @return int
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function resolvesToId()
    {
        return $this->resolvesToId;
    }

    /**
     * @return string
     */
    public function resolvesToName()
    {
        return $this->resolvesToName;
    }

    /**
     * @return string
     */
    public function resolvesToDescription()
    {
        return $this->resolvesToDescription;
    }

    public function __toString()
    {
        return $this->name();
    }

    private function __construct()
    {
    }
}
