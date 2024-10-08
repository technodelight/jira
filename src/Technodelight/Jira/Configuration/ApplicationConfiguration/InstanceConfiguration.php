<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class InstanceConfiguration implements RegistrableConfiguration
{
    private string $name;
    private string $domain;
    private ?string $username;
    private ?string $password;
    private string $worklogHandler;
    /**
     * @var array
     */
    private $config;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->config = $config;
        $instance->name = $config['name'];
        $instance->domain = $config['domain'];
        $instance->username = $config['email'] ?? $config['username'] ?? null;
        $instance->password = $config['token'] ?? $config['password'] ?? null;
        $instance->worklogHandler = $config['worklogHandler'];

        return $instance;
    }

    public function name()
    {
        return $this->name;
    }

    public function domain()
    {
        return $this->domain;
    }

    public function username()
    {
        return $this->username;
    }

    public function password()
    {
        return $this->password;
    }

    public function worklogHandler()
    {
        return $this->worklogHandler;
    }

    private function __construct()
    {
    }

    /**
     * @return string
     */
    public function servicePrefix(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function configAsArray(): array
    {
        return $this->config;
    }
}
