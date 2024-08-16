<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration\BranchNameGeneratorConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

/** @SuppressWarnings(PHPMD.StaticAccess,PHPMD.UnusedPrivateField) */
class GitConfiguration implements RegistrableConfiguration
{
    private int $maxBranchNameLength;
    private BranchNameGeneratorConfiguration $branchNameGenerator;
    private array $config;

    public static function fromArray(array $config): GitConfiguration
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

    public function servicePrefix(): string
    {
        return 'git';
    }

    /**
     * @return array
     */
    public function configAsArray(): array
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
