<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration;

use Technodelight\Jira\Configuration\ApplicationConfiguration\Service\RegistrableConfiguration;

class EditorConfiguration implements RegistrableConfiguration
{
    /**
     * @var string
     */
    private $executable;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->executable = $config['executable'];

        return $instance;
    }

    public function executable()
    {
        return $this->executable;
    }

    public function servicePrefix()
    {
        return 'editor';
    }
}
