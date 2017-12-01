<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class GitHubConfiguration implements RegistrableConfiguration
{
    private $token;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->token = $config['apiToken'];

        return $instance;
    }

    /**
     * @return string
     */
    public function token()
    {
        return $this->token;
    }

    public function servicePrefix()
    {
        return 'github';
    }

    private function __construct()
    {
    }
}
