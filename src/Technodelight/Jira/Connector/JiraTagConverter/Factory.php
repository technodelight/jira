<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\JiraTagConverter;

use Symfony\Component\Console\Terminal;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\ITermConfiguration;
use Technodelight\Jira\Console\Application;
use Technodelight\JiraTagConverter\JiraTagConverter;

class Factory
{
    public function __construct(
        private readonly Terminal $terminal,
        private readonly ITermConfiguration $configuration
    ) {
    }

    public function build(array $opts = []): JiraTagConverter
    {
        $opts['terminalWidth'] = $opts['terminalWidth'] ?? $this->terminal->getWidth();
        // do not touch image sequences if iterm image rendering is enabled
        $opts['images'] = $opts['images'] ?? !$this->configuration->renderImages();

        return new JiraTagConverter($opts);
    }
}
