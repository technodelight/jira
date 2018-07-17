<?php

namespace Technodelight\Jira\Api\EditApp;

class EditApp
{
    /**
     * @var \Technodelight\Jira\Api\EditApp\Driver
     */
    private $driver;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    public function edit($title, $content)
    {
        return $this->driver->edit($title, $content);
    }
}
