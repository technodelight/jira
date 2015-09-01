<?php

namespace Technodelight\Jira\Configuration;

class GlobalConfiguration extends Configuration
{
    public static function initFromDirectory($iniFilePath)
    {
        if (!is_file($iniFilePath . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME)) {
            return new self();
        }

        return new self(self::parseIniFile($iniFilePath));
    }
}
