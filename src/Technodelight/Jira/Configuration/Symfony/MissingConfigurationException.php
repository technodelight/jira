<?php

namespace Technodelight\Jira\Configuration\Symfony;

class MissingConfigurationException extends ConfigurationFileException
{
    public static function fromPath($path)
    {
        $exc = new self('No configuration found!');
        $exc->setPath($path);
        return $exc;
    }
}
