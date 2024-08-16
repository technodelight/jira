<?php

declare(strict_types=1);

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

/** @SuppressWarnings(PHPMD.UnusedPrivateField) */
class BranchNameGeneratorConfiguration implements RegistrableConfiguration
{
    private array $defaultPatterns = [
        'preg_match("~^Release ~", issue.summary())' => ['pattern' => 'release/{clean(substr(issue.summary(), 8))}'],
        'preg_match("~.*~", issue.summary())' => ['pattern' => 'feature/{issueKey}-{summary}'],
    ];
    /**
     * @var array "regex" => ["pattern" => "pattern/{templeStuff}"]
     */
    private array $patterns;
    private string $separator;
    private string $whitelist;
    private array $remove;
    private array $replace;
    private array $config;

    public static function fromArray(array $config): BranchNameGeneratorConfiguration
    {
        $instance = new self;
        $instance->config = $config;
        $instance->patterns = !empty($config['patterns']) ? $config['patterns'] : $instance->defaultPatterns;
        $instance->separator = $config['separator'];
        $instance->whitelist = $config['whitelist'];
        $instance->remove = $config['remove'];
        $instance->replace = $config['replace'];

        return $instance;
    }

    public function patterns(): array
    {
        $patterns = [];
        foreach ($this->patterns as $regex => $def) {
            $patterns[$regex] = $def['pattern'];
        }
        return $patterns;
    }

    public function separator(): string
    {
        return $this->separator;
    }

    public function whitelist(): string
    {
        return $this->whitelist;
    }

    public function remove(): array
    {
        return $this->remove;
    }

    public function replace(): array
    {
        return $this->replace;
    }

    public function servicePrefix(): string
    {
        return 'branch_name_generator';
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
