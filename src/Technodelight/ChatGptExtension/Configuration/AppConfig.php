<?php

declare(strict_types=1);

namespace Technodelight\ChatGptExtension\Configuration;

use Technodelight\ChatGptExtension\Api\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration;

class AppConfig
{
    private string $apiKey;
    private ?string $organization;

    private string $model;

    public static function fromArray(array $config): AppConfig
    {
        $instance = new self;
        $instance->apiKey = $config['apiKey'] ?? '';
        $instance->organization = $config['organization'] ?? null;
        $instance->model = $config['model'] ?? Api::MODEL;

        return $instance;
    }

    public static function fromConfig(ApplicationConfiguration $configuration): AppConfig
    {
        return self::fromArray($configuration->configAsArray()['chatgpt'] ?? []);
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function organization(): ?string
    {
        return $this->organization;
    }

    public function model(): string
    {
        return $this->model;
    }

    public function servicePrefix(): string
    {
        return 'chatgpt';
    }

    public function configAsArray(): array
    {
        return ['apiKey' => $this->apiKey, 'organization' => $this->organization, 'model' => $this->model];
    }
}
