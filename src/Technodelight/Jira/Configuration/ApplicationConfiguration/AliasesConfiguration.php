<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class AliasesConfiguration implements RegistrableConfiguration
{
    /**
     * @var AliasConfiguration[]
     */
    private $aliases;
    /**
     * @var array
     */
    private $config;

    public static function fromArray(array $config)
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

    public function items()
    {
        return $this->aliases;
    }

    public function aliasToIssueKey($alias)
    {
        foreach ($this->items() as $item) {
            if ($item->alias() == $alias) {
                return $item->issueKey();
            }
        }

        return $alias;
    }

    public function issueKeyToAlias($issueKey)
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
