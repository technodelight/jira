<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

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

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->github = GitHubConfiguration::fromArray($config['github']);
        $instance->git = GitConfiguration::fromArray($config['git']);
        $instance->tempo = TempoConfiguration::fromArray($config['tempo']);
        $instance->iterm = ITermConfiguration::fromArray($config['iterm']);
        $instance->editor = EditorConfiguration::fromArray($config['editor']);

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

    public function servicePrefix()
    {
        return 'integrations';
    }

    private function __construct()
    {
    }
}
