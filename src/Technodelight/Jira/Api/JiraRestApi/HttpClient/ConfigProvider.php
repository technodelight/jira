<?php

namespace Technodelight\Jira\Api\JiraRestApi\HttpClient;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration;

class ConfigProvider
{
    /**
     * @var ApplicationConfiguration
     */
    private $config;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ApplicationConfiguration $config, ContainerInterface $container)
    {
        $this->config = $config;
        $this->container = $container;
    }

    public function username()
    {
        if ($instance = $this->instance()) {
            return $this->config->instance($instance)['username'];
        }
        return $this->config->username();
    }

    public function password()
    {
        if ($instance = $this->instance()) {
            return $this->config->instance($instance)['password'];
        }
        return $this->config->password();
    }

    public function domain()
    {
        if ($instance = $this->instance()) {
            return $this->config->instance($instance)['domain'];
        }
        return $this->config->domain();
    }

    /**
     * @return string|null
     */
    private function instance()
    {
        return $this->container->getParameter('app.jira.instance');
    }
}
