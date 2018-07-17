<?php

namespace Technodelight\Jira\Connector\EditApp;

use Technodelight\Jira\Api\EditApp\AdaptableFactoryWithPreference;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\EditorConfiguration;

class Factory
{
    /**
     * @var EditorConfiguration
     */
    private $configuration;

    public function __construct(EditorConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return \Technodelight\Jira\Api\EditApp\EditApp
     */
    public function build()
    {
        return AdaptableFactoryWithPreference::build($this->configuration->executable());
    }
}
