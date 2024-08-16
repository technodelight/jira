<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\EditApp;

use Technodelight\CliEditorInput\AdaptableFactoryWithPreference;
use Technodelight\CliEditorInput\CliEditorInput;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\EditorConfiguration;

class Factory
{
    public function __construct(private readonly EditorConfiguration $configuration) {}

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    public function build(): CliEditorInput
    {
        return AdaptableFactoryWithPreference::build($this->configuration->executable());
    }
}
