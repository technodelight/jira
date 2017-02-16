<?php

namespace Technodelight\Jira\Configuration\Symfony;

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
