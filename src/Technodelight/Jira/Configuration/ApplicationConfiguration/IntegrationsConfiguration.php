<?php

declare(strict_types=1);

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\EditorConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\ITermConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

/** @SuppressWarnings(PHPMD.StaticAccess) */
class IntegrationsConfiguration implements RegistrableConfiguration
{
    private GitConfiguration $git;
    private ITermConfiguration $iterm;
    private EditorConfiguration $editor;
    private array $config;

    public static function fromArray(array $config): IntegrationsConfiguration
    {
        $instance = new self;
        $instance->config = $config;
        $instance->git = GitConfiguration::fromArray($config['git']);
        $instance->iterm = ITermConfiguration::fromArray($config['iterm']);
        $instance->editor = EditorConfiguration::fromArray($config['editor']);

        return $instance;
    }

    public function git(): GitConfiguration
    {
        return $this->git;
    }

    public function iterm(): ITermConfiguration
    {
        return $this->iterm;
    }

    public function editor(): EditorConfiguration
    {
        return $this->editor;
    }

    public function servicePrefix(): string
    {
        return 'integrations';
    }

    public function configAsArray(): array
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
