<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

class AliasConfiguration
{
    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $issueKey;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->alias = $config['alias'];
        $instance->issueKey = $config['issueKey'];

        return $instance;
    }
    public function alias()
    {
        return $this->alias;
    }

    public function issueKey()
    {
        return $this->issueKey;
    }

    private function __construct()
    {
    }
}
