<?php

namespace Technodelight\Jira\Configuration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\FiltersConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\InstancesConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\ProjectConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RenderersConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\TransitionsConfiguration;

class ApplicationConfiguration implements RegistrableConfiguration
{
    /**
     * @var InstancesConfiguration
     */
    private $instances;
    /**
     * @var IntegrationsConfiguration
     */
    private $integrations;
    /**
     * @var ProjectConfiguration
     */
    private $project;
    /**
     * @var TransitionsConfiguration
     */
    private $transitions;
    /**
     * @var AliasesConfiguration
     */
    private $aliases;
    /**
     * @var FiltersConfiguration
     */
    private $filters;
    /**
     * @var RenderersConfiguration
     */
    private $renderers;
    /**
     * @var array
     */
    private $config;

    /**
     * @return InstancesConfiguration
     */
    public function instances()
    {
        return $this->instances;
    }

    /**
     * @return IntegrationsConfiguration
     */
    public function integrations()
    {
        return $this->integrations;
    }

    /**
     * @return ProjectConfiguration
     */
    public function project()
    {
        return $this->project;
    }

    /**
     * @return TransitionsConfiguration
     */
    public function transitions()
    {
        return $this->transitions;
    }

    /**
     * @return AliasesConfiguration
     */
    public function aliases()
    {
        return $this->aliases;
    }

    /**
     * @return FiltersConfiguration
     */
    public function filters()
    {
        return $this->filters;
    }

    /**
     * @return RenderersConfiguration
     */
    public function renderers()
    {
        return $this->renderers;
    }

    public static function fromSymfonyConfigArray(array $config)
    {
        $configuration = new self;
        $configuration->config = $config;

        $configuration->instances = InstancesConfiguration::fromArray($config['instances']);
        $configuration->integrations = IntegrationsConfiguration::fromArray(isset($config['integrations']) ? $config['integrations'] : []);
        $configuration->project = ProjectConfiguration::fromArray(isset($config['project']) ? $config['project'] : []);
        $configuration->transitions = TransitionsConfiguration::fromArray(isset($config['transitions']) ? $config['transitions'] : []);
        $configuration->aliases = AliasesConfiguration::fromArray(isset($config['aliases']) ? $config['aliases'] : []);
        $configuration->filters = FiltersConfiguration::fromArray(isset($config['filters']) ? $config['filters'] : []);
        $configuration->renderers = RenderersConfiguration::fromArray(isset($config['renderers']) ? $config['renderers'] : []);

        return $configuration;
    }

    public function servicePrefix()
    {
        return 'technodelight.jira.config';
    }

    /**
     * @return array
     */
    public function configAsArray()
    {
        return $this->config;
    }
}
