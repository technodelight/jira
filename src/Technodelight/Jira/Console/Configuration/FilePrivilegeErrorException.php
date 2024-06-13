<?php

namespace Technodelight\Jira\Console\Configuration;

class FilePrivilegeErrorException extends ConfigurationFileException
{
    public static function fromUnreadablePath($path)
    {
        $exc = new self('Cannot read configuration!');
        $exc->setPath($path);
        return $exc;
    }

    public static function fromInvalidPermAndFilePath($perms, $path)
    {
        $exc = new self(
            sprintf('Configuration %s cannot be readable by others! 0%o should be 0600)', $path, $perms)
    );
        $exc->setPath($path);
        return $exc;
    }
}
