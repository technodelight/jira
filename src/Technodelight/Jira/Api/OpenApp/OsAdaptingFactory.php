<?php

namespace Technodelight\Jira\Api\OpenApp;

use Technodelight\Jira\Api\OpenApp\Driver\Generic;
use Technodelight\Jira\Api\OpenApp\Driver\Opn;
use Technodelight\Jira\Api\OpenApp\Driver\XdgOpen;
use Technodelight\ShellExec\Passthru;

class OsAdaptingFactory
{
    public static function create()
    {
        return new OpenApp(self::driver());
    }

    private static function driver()
    {
        switch(php_uname('s')) {
            case 'Darwin': return new Generic(new Passthru());
            case 'Linux': return new XdgOpen(new Passthru());
            default: return new Opn(new Passthru());
        }
    }
}
