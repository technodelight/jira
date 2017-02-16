<?php

namespace Technodelight\Jira\Configuration\Symfony;

class FilePriviledgeErrorException extends ConfigurationFileException
{
    public static function fromUnreadablePath($path)
    {
        $exc = new self('Cannot read configuration!');
        $exc->setPath($path);
        return $exc;
    }

    public static function fromInvalidPermAndPath($perms, $path)
    {
        $exc = new self(sprintf('Configuration cannot be readable by others! %s should be 0600)', $perms));
        $exc->setPath($path);
        return $exc;
    }
}
