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
        return $this->instance()->username();
    }

    public function password()
    {
        return $this->instance()->password();
    }

    public function domain()
    {
        return $this->instance()->domain();
    }

    private function instance()
    {
        return $this->config->instances()->findByName(
            $this->container->getParameter('app.jira.instance')
        );
    }
}
