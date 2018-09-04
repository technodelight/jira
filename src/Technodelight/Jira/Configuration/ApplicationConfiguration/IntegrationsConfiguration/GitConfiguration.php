<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration\BranchNameGeneratorConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class GitConfiguration implements RegistrableConfiguration
{
    /**
     * @var int
     */
    private $maxBranchNameLength;
    /**
     * @var BranchNameGeneratorConfiguration
     */
    private $branchNameGenerator;
    /**
     * @var array
     */
    private $config;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->config = $config;
        $instance->maxBranchNameLength = $config['maxBranchNameLength'];
        $instance->branchNameGenerator = BranchNameGeneratorConfiguration::fromArray($config['branchNameGenerator']);

        return $instance;
    }

    /**
     * @return int
     */
    public function maxBranchNameLength()
    {
        return $this->maxBranchNameLength;
    }

    /**
     * @return BranchNameGeneratorConfiguration
     */
    public function branchNameGenerator()
    {
        return $this->branchNameGenerator;
    }

    public function servicePrefix()
    {
        return 'git';
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
