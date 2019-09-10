<?php

namespace Technodelight\Jira\Console\Configuration;

class MissingConfigurationException extends ConfigurationFileException
{
    public static function noConfigsFound()
    {
        return new self('Unable to find any configuration file!');
    }

    public static function fromPath($path)
    {
        $exc = new self('No configuration found!');
        $exc->setPath($path);
        return $exc;
    }
}
