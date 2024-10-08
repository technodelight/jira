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

/** @SuppressWarnings(PHPMD.StaticAccess) */
class ApplicationConfiguration implements RegistrableConfiguration
{
    private InstancesConfiguration $instances;
    private IntegrationsConfiguration $integrations;
    private ProjectConfiguration $project;
    private TransitionsConfiguration $transitions;
    private AliasesConfiguration $aliases;
    private FiltersConfiguration $filters;
    private RenderersConfiguration $renderers;
    private array $config;

    public function instances(): InstancesConfiguration
    {
        return $this->instances;
    }

    public function integrations(): IntegrationsConfiguration
    {
        return $this->integrations;
    }

    public function project(): ProjectConfiguration
    {
        return $this->project;
    }

    public function transitions(): TransitionsConfiguration
    {
        return $this->transitions;
    }

    public function aliases(): AliasesConfiguration
    {
        return $this->aliases;
    }

    public function filters(): FiltersConfiguration
    {
        return $this->filters;
    }

    public function renderers(): RenderersConfiguration
    {
        return $this->renderers;
    }

    public static function fromSymfonyConfigArray(array $config): ApplicationConfiguration
    {
        $configuration = new self;
        $configuration->config = $config;

        $configuration->instances = InstancesConfiguration::fromArray($config['instances']);
        $configuration->integrations = IntegrationsConfiguration::fromArray($config['integrations'] ?? []);
        $configuration->project = ProjectConfiguration::fromArray($config['project'] ?? []);
        $configuration->transitions = TransitionsConfiguration::fromArray($config['transitions'] ?? []);
        $configuration->aliases = AliasesConfiguration::fromArray($config['aliases'] ?? []);
        $configuration->filters = FiltersConfiguration::fromArray($config['filters'] ?? []);
        $configuration->renderers = RenderersConfiguration::fromArray($config['renderers'] ?? []);

        return $configuration;
    }

    public function servicePrefix(): string
    {
        return 'technodelight.jira.config';
    }

    public function configAsArray(): array
    {
        return $this->config;
    }
}
