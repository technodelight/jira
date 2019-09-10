<?php

namespace Technodelight\Jira\Console\Configuration;

abstract class ConfigurationFileException extends \UnexpectedValueException
{
    private $path;

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}
