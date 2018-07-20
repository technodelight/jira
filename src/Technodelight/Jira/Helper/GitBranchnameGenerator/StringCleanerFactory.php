<?php

namespace Technodelight\Jira\Helper\GitBranchnameGenerator;

use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration\BranchNameGeneratorConfiguration;

class StringCleanerFactory
{
    /**
     * @var BranchNameGeneratorConfiguration
     */
    private $config;

    public function __construct(BranchNameGeneratorConfiguration $config)
    {
        $this->config = $config;
    }

    public function build()
    {
        return new StringCleaner(
            $this->config->whitelist(),
            $this->config->remove(),
            $this->config->replace(),
            $this->config->separator()
        );
    }
}
