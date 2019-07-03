<?php

namespace Technodelight\Jira\Connector\EditApp;

use Technodelight\CliEditorInput\AdaptableFactoryWithPreference;
use Technodelight\CliEditorInput\CliEditorInput;
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
     * @return CliEditorInput
     */
    public function build()
    {
        return AdaptableFactoryWithPreference::build($this->configuration->executable());
    }
}
