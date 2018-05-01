<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class AliasesConfiguration implements RegistrableConfiguration
{
    /**
     * @var AliasConfiguration[]
     */
    private $aliases;

    public static function fromArray(array $config)
    {
        $instance = new self;

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

    public function servicePrefix()
    {
        return 'aliases';
    }

    private function __construct()
    {
    }
}
