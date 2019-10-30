<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\EditorConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\ITermConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class IntegrationsConfiguration implements RegistrableConfiguration
{
    /**
     * @var GitConfiguration
     */
    private $git;
    /**
     * @var ITermConfiguration
     */
    private $iterm;
    /**
     * @var EditorConfiguration
     */
    private $editor;
    /**
     * @var array
     */
    private $config;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->config = $config;
        $instance->git = GitConfiguration::fromArray($config['git']);
        $instance->iterm = ITermConfiguration::fromArray($config['iterm']);
        $instance->editor = EditorConfiguration::fromArray($config['editor']);

        return $instance;
    }

    public function git()
    {
        return $this->git;
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
