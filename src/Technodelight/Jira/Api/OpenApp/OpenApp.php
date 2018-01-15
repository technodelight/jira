<?php

namespace Technodelight\Jira\Api\OpenApp;

class OpenApp
{
    /**
     * @var \Technodelight\Jira\Api\OpenApp\Driver
     */
    private $driver;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    public function open($uri)
    {
        $this->driver->open($uri);
    }
}
