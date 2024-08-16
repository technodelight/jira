<?php

declare(strict_types=1);

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

/** @SuppressWarnings(PHPMD.StaticAccess) */
class AliasesConfiguration implements RegistrableConfiguration
{
    /** @var AliasConfiguration[] */
    private array $aliases;
    private array $config;

    public static function fromArray(array $config): AliasesConfiguration
    {
        $instance = new self;
        $instance->config = $config;

        $instance->aliases = array_map(
            function (array $transition) {
                return AliasConfiguration::fromArray($transition);
            },
            $config
        );

        return $instance;
    }

    public function items(): array
    {
        return $this->aliases;
    }

    public function aliasToIssueKey($alias): string
    {
        foreach ($this->items() as $item) {
            if ($item->alias() == $alias) {
                return $item->issueKey();
            }
        }

        return $alias;
    }

    public function issueKeyToAlias($issueKey): string
    {
        foreach ($this->items() as $item) {
            if ($item->issueKey() == $issueKey) {
                return $item->alias();
            }
        }
        return $issueKey;
    }

    public function servicePrefix(): string
    {
        return 'aliases';
    }

    public function configAsArray(): array
    {
        return $this->config;
    }

    private function __construct()
    {
    }
}
