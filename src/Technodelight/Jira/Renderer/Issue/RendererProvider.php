<?php

namespace Technodelight\Jira\Renderer\Issue;

class RendererProvider
{
    /**
     * @var Renderer[]
     */
    private $renderers;

    public function __construct(array $renderers)
    {
        $this->renderers = $renderers;
    }

    public function has($name)
    {
        return isset($this->renderers[$name]);
    }

    public function get($name)
    {
        return $this->renderers[$name];
    }

    public function all()
    {
        return $this->renderers;
    }
}
