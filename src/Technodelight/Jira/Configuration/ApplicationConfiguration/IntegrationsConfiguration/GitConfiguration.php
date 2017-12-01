<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class GitConfiguration implements RegistrableConfiguration
{
    private $maxBranchNameLength;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->maxBranchNameLength = $config['maxBranchNameLength'];

        return $instance;
    }

    /**
     * @return int
     */
    public function maxBranchNameLength()
    {
        return $this->maxBranchNameLength;
    }

    public function servicePrefix()
    {
        return 'git';
    }

    private function __construct()
    {
    }
}
