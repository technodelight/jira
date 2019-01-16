<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class GitHubConfiguration implements RegistrableConfiguration
{
    /**
     * @var array
     */
    private $config;
    /**
     * @var string|null
     */
    private $token;
    /**
     * @var string|null
     */
    private $owner;
    /**
     * @var string|null
     */
    private $repo;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->config = $config;
        $instance->token = $config['apiToken'];
        $instance->owner = $config['owner'];
        $instance->repo = $config['repo'];

        return $instance;
    }

    /**
     * @return string
     */
    public function token()
    {
        return $this->token;
    }

    /**
     * @return string|null
     */
    public function owner()
    {
        return $this->owner;
    }

    /**
     * @return string|null
     */
    public function repo()
    {
        return $this->repo;
    }

    public function servicePrefix()
    {
        return 'github';
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
