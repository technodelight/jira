<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\DaemonConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\EditorConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitHubConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\ITermConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\TempoConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class IntegrationsConfiguration implements RegistrableConfiguration
{
    /**
     * @var GitHubConfiguration
     */
    private $github;
    /**
     * @var GitConfiguration
     */
    private $git;
    /**
     * @var TempoConfiguration
     */
    private $tempo;
    /**
     * @var ITermConfiguration
     */
    private $iterm;
    /**
     * @var EditorConfiguration
     */
    private $editor;
    /**
     * @var DaemonConfiguration
     */
    private $daemon;
    /**
     * @var array
     */
    private $config;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->config = $config;
        $instance->github = GitHubConfiguration::fromArray($config['github']);
        $instance->git = GitConfiguration::fromArray($config['git']);
        $instance->tempo = TempoConfiguration::fromArray($config['tempo']);
        $instance->iterm = ITermConfiguration::fromArray($config['iterm']);
        $instance->editor = EditorConfiguration::fromArray($config['editor']);
        $instance->daemon = DaemonConfiguration::fromArray($config['daemon']);

        return $instance;
    }

    public function github()
    {
        return $this->github;
    }

    public function git()
    {
        return $this->git;
    }

    public function tempo()
    {
        return $this->tempo;
    }

    public function iterm()
    {
        return $this->iterm;
    }

    public function editor()
    {
        return $this->editor;
    }

    public function daemon()
    {
        return $this->daemon;
    }

    public function servicePrefix()
    {
        return 'integrations';
    }

    /**
     * @return array
     */
    public function configAsArray()
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
