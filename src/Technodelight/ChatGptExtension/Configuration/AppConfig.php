<?php

declare(strict_types=1);

namespace Technodelight\ChatGptExtension\Configuration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class AppConfig implements RegistrableConfiguration
{
    private string $apiKey;
    private string $organization;

    public static function fromArray(array $config): AppConfig
    {
        $instance = new self;
        $instance->apiKey = $config['apiKey'] ?? '';
        $instance->organization = $config['organization'] ?? '';

        return $instance;
    }
    public function servicePrefix(): string
    {
        return 'chatgpt';
    }

    public function configAsArray(): array
    {
        return ['apiKey' => $this->apiKey, 'organization' => $this->organization];
    }
}
